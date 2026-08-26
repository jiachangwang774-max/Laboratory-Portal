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
            ->whereNotNull('group_name')
            ->whereNotNull('user_id');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('student_id', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();
        $apps  = $query->forPage($page, $size)->orderBy('audit_time', 'desc')->get();

        $list = $apps->map(function (SignApplication $app) use ($courseId, $labId) {
            $user = SysUser::find($app->user_id);

            // 作业按班级发布：统计「该实验室 +（该学员班级 或 未指定班级）」的作业数
            $homeworkQuery = TrainHomework::where('lab_id', $labId)
                ->where(function ($q) use ($app) {
                    $q->where('group_name', $app->group_name)
                      ->orWhereNull('group_name');
                });
            if ($courseId) $homeworkQuery->where('course_id', $courseId);
            $homeworkIds = $homeworkQuery->pluck('homework_id');

            $submits = HomeworkSubmit::with('homework')
                ->where('user_id', $app->user_id)
                ->whereIn('homework_id', $homeworkIds)
                ->get();
            $submitCount = $submits->count();
            $avgScore = $submits->whereNotNull('score')->avg('score');

            // 按时率：已提交作业中「提交时间不晚于截止时间」的占比（与 detail 接口一致）
            $onTimeCount = $submits->filter(function (HomeworkSubmit $s) {
                return $s->homework->deadline && $s->submit_time <= $s->homework->deadline;
            })->count();

            $checkinCount = CheckinRecord::where('user_id', $app->user_id)->count();

            $homeworkCount = $homeworkIds->count();
            return [
                'userId'        => $app->user_id,
                'realName'      => $user->real_name ?? $app->name,
                'studentId'     => $app->student_id,
                'className'     => $app->group_name ?? '',
                'homeworkCount' => $homeworkCount,
                'submitCount'   => $submitCount,
                'avgScore'      => $avgScore ? round($avgScore, 1) : null,
                'submitRate'    => $homeworkCount > 0 ? round($submitCount / $homeworkCount * 100) . '%' : 'N/A',
                'onTimeRate'    => $submitCount > 0 ? round($onTimeCount / $submitCount * 100) . '%' : 'N/A',
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

            // 满分 = 该作业所有题目 score 累加（而非固定 100）
            $fullScore = collect($s->homework->questions ?? [])->sum('score');

            return [
                'homeworkId'    => $s->homework_id,
                'homeworkTitle' => $s->homework->homework_title ?? '',
                'courseName'    => $s->homework->course->course_name ?? '',
                'deadline'      => $s->homework->deadline,
                'submitTime'    => $s->submit_time,
                'score'         => $s->score,
                'fullScore'     => $fullScore,
                'remark'        => $s->remark,
                'onTime'        => $onTime,
            ];
        });

        $total = $submits->count();
        // 完成率 = 已提交数 / 该学员已报名课程下的作业总数
        $totalHomework = TrainHomework::whereIn('course_id', $courseIds)->count();
        return [
            'userId'       => $userId,
            'realName'     => $user->real_name ?? '',
            'studentId'    => $user->student_id ?? '',
            'homeworkList' => $list->values(),
            'avgScore'     => count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : null,
            'submitRate'   => $totalHomework > 0 ? round($total / $totalHomework * 100) . '%' : 'N/A',
            'onTimeRate'   => $total > 0 ? round($onTimeCount / $total * 100) . '%' : 'N/A',
        ];
    }
}
