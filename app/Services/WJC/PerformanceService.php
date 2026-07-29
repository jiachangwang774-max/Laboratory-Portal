<?php

namespace App\Services\WJC;

use App\Models\HomeworkSubmit;
use App\Models\TrainHomework;
use App\Models\TrainSign;
use App\Models\SysUser;
use Illuminate\Support\Facades\DB;

class PerformanceService
{
    public function list(int $page = 1, int $size = 10, ?int $courseId = null, ?string $keyword = null): array
    {
        // 从已报名学员出发
        $query = TrainSign::with(['user', 'course'])
            ->where('status', 1)
            ->orderBy('sign_time', 'desc');

        if ($courseId) $query->where('course_id', $courseId);
        if ($keyword) {
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('real_name', 'like', "%{$keyword}%")
                  ->orWhere('student_id', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();
        $signs = $query->forPage($page, $size)->get();

        $list = $signs->map(function ($sign) {
            $homeworkIds = TrainHomework::where('course_id', $sign->course_id)->pluck('homework_id');
            $homeworkCount = $homeworkIds->count();
            $submits = HomeworkSubmit::where('user_id', $sign->user_id)->whereIn('homework_id', $homeworkIds)->get();
            $submitCount = $submits->count();
            $avgScore = $submits->whereNotNull('score')->avg('score');

            return [
                'userId'        => $sign->user_id,
                'realName'      => $sign->user->real_name ?? '',
                'studentId'     => $sign->user->student_id ?? '',
                'courseName'    => $sign->course->course_name ?? '',
                'homeworkCount' => $homeworkCount,
                'submitCount'   => $submitCount,
                'avgScore'      => $avgScore ? round($avgScore, 1) : null,
                'submitRate'    => $homeworkCount > 0 ? round($submitCount / $homeworkCount * 100) . '%' : 'N/A',
            ];
        });

        return ['total' => $total, 'list' => $list->values()];
    }

    public function detail(int $userId, ?int $courseId = null): array
    {
        $user = SysUser::find($userId);

        $courseIds = TrainSign::where('user_id', $userId)->where('status', 1)->pluck('course_id');
        if ($courseId) $courseIds = $courseIds->intersect([$courseId]);

        $submits = HomeworkSubmit::with('homework.course')
            ->where('user_id', $userId)
            ->whereHas('homework', function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds);
            })
            ->orderBy('submit_time', 'desc')
            ->get();

        $onTimeCount = 0;
        $scores = [];
        $list = $submits->map(function ($s) use (&$onTimeCount, &$scores) {
            $onTime = $s->homework->deadline && $s->submit_time <= $s->homework->deadline;
            if ($onTime) $onTimeCount++;
            if ($s->score !== null) $scores[] = $s->score;
            return [
                'homeworkId'    => $s->homework_id,
                'homeworkTitle' => $s->homework->homework_title ?? '',
                'courseName'    => $s->homework->course->course_name ?? '',
                'deadline'      => $s->homework->deadline,
                'submitTime'    => $s->submit_time,
                'score'         => $s->score,
                'remark'        => $s->remark,
                'onTime'        => $onTime,
            ];
        });

        $total = $submits->count();
        return [
            'userId'       => $userId,
            'realName'     => $user->real_name ?? '',
            'studentId'    => $user->student_id ?? '',
            'homeworkList' => $list->values(),
            'avgScore'     => count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : null,
            'submitRate'   => $total > 0 ? round($total / max($total, 1) * 100) . '%' : 'N/A',
            'onTimeRate'   => $total > 0 ? round($onTimeCount / $total * 100) . '%' : 'N/A',
        ];
    }
}
