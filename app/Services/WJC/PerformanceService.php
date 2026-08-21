<?php

namespace App\Services\WJC;

use App\Models\CheckinRecord;
use App\Models\HomeworkSubmit;
use App\Models\SignApplication;
use App\Models\TrainHomework;
use App\Models\TrainSign;
use App\Models\SysUser;

class PerformanceService
{
    public function list(int $page = 1, int $size = 10, ?int $courseId = null, ?string $keyword = null): array
    {
        $labId = auth('admin_api')->user()->lab_id ?? 'software';

        // 以 SignApplication（培训名单/分班表）为主表，分班里有人即使作业/签到为 0 也显示
        $query = SignApplication::where('audit_status', 1)
            ->where('lab_id', $labId)
            ->whereNotNull('group_name');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('student_id', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();
        $apps  = $query->forPage($page, $size)->orderBy('audit_time', 'desc')->get();

        $list = $apps->map(function (SignApplication $app) use ($courseId) {
            $user = SysUser::find($app->user_id);

            $homeworkQuery = TrainHomework::query();
            if ($courseId) $homeworkQuery->where('course_id', $courseId);
            $homeworkIds = $homeworkQuery->pluck('homework_id');

            $submits = HomeworkSubmit::where('user_id', $app->user_id)
                ->whereIn('homework_id', $homeworkIds)
                ->get();
            $submitCount = $submits->count();
            $avgScore = $submits->whereNotNull('score')->avg('score');

            $checkinCount = CheckinRecord::where('user_id', $app->user_id)->count();

            return [
                'userId'        => $app->user_id,
                'realName'      => $user->real_name ?? $app->name,
                'studentId'     => $app->student_id,
                'className'     => $app->group_name ?? '',
                'homeworkCount' => $homeworkIds->count(),
                'submitCount'   => $submitCount,
                'avgScore'      => $avgScore ? round($avgScore, 1) : null,
                'submitRate'    => $homeworkIds->count() > 0 ? round($submitCount / $homeworkIds->count() * 100) . '%' : 'N/A',
                'checkinCount'  => $checkinCount,
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
