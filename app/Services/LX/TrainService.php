<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\HomeworkSubmit;
use App\Models\TrainCourse;
use App\Models\TrainHomework;
use App\Models\TrainSign;
use App\Models\TrainTraining;
use App\Traits\LogTrait;

class TrainService
{
    use LogTrait;

    /**
     * 学生端课程详情
     *
     * 仅返回 status=1 的上架课程，同时返回当前学生的报名状态
     */
    public function courseDetail(int $courseId, int $userId): array
    {
        $course = TrainCourse::enabled()->find($courseId);

        if (!$course) {
            throw new BusinessException('课程不存在或已下架', ResponseCode::DATA_NOT_FOUND);
        }

        // 查询当前学生对该课程的报名状态
        $sign = TrainSign::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        // 已报名人数
        $signCount = TrainSign::where('course_id', $courseId)
            ->where('status', 1)
            ->count();

        return [
            'courseId'    => $course->course_id,
            'courseName'  => $course->course_name,
            'courseDesc'  => $course->course_desc,
            'coverImg'    => $course->cover_img,
            'instructor'  => $course->instructor,
            'courseDate'  => $course->course_date,
            'location'    => $course->location,
            'startTime'   => $course->start_time,
            'endTime'     => $course->end_time,
            'maxSign'     => $course->max_sign,
            'signCount'   => $signCount,
            'signStatus'  => $sign ? $sign->status : null,   // null=未报名, 1=已报名, 0=已取消
            'signTime'    => $sign ? $sign->sign_time : null,
        ];
    }

    /**
     * 培训详情
     *
     * 仅返回 status=1 的培训
     */
    public function trainingDetail(int $trainingId): array
    {
        $training = TrainTraining::enabled()->find($trainingId);

        if (!$training) {
            throw new BusinessException('培训不存在或已下架', ResponseCode::DATA_NOT_FOUND);
        }

        return [
            'trainingTime'    => $training->training_time,
            'trainingContent' => $training->training_content,
        ];
    }

    /**
     * 我的作业列表
     *
     * 只查询已通过审核课程下的作业，可按课程筛选，按创建时间倒序
     */
    public function homeworkList(int $userId, int $page = 1, int $size = 10, ?int $courseId = null): array
    {
        $approvedCourseIds = TrainSign::where('user_id', $userId)
            ->where('status', 1)
            ->pluck('course_id');

        if ($approvedCourseIds->isEmpty()) {
            return ['total' => 0, 'list' => []];
        }

        $query = TrainHomework::whereIn('course_id', $approvedCourseIds)
            ->orderBy('create_time', 'desc');

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $total = $query->count();
        $list  = $query->forPage($page, $size)->get()->map(function (TrainHomework $homework) {
            return [
                'homeworkId'      => $homework->homework_id,
                'courseId'        => $homework->course_id,
                'homeworkTitle'   => $homework->homework_title,
                'homeworkContent' => $homework->homework_content,
                'deadline'        => $homework->deadline,
                'createTime'      => $homework->create_time,
            ];
        });

        return [
            'total' => $total,
            'list'  => $list->values(),
        ];
    }

    /**
     * 提交作业
     *
     * 校验作业存在 → 校验已通过课程审核 → 校验未重复提交 → 处理文件上传 → 写入提交记录
     */
    public function homeworkSubmit(int $userId, int $homeworkId, ?string $content = null, $file = null): array
    {
        /** @var TrainHomework $homework */
        $homework = TrainHomework::find($homeworkId);

        if (!$homework) {
            throw new BusinessException('作业不存在', ResponseCode::DATA_NOT_FOUND);
        }

        // 校验用户已报名该课程
        $approved = TrainSign::where('user_id', $userId)
            ->where('course_id', $homework->course_id)
            ->where('status', 1)
            ->exists();

        if (!$approved) {
            throw new BusinessException('您未通过该课程的报名审核，无法提交作业', ResponseCode::FORBIDDEN);
        }

        // 校验未重复提交
        $exists = HomeworkSubmit::where('user_id', $userId)
            ->where('homework_id', $homeworkId)
            ->exists();

        if ($exists) {
            throw new BusinessException('您已提交过该作业', ResponseCode::DUPLICATE_SUBMIT);
        }

        // 处理文件上传
        $filePath = '';
        if ($file && $file->isValid()) {
            $filePath = $file->store('homework_files', 'public');
        }

        $submit = HomeworkSubmit::create([
            'user_id'        => $userId,
            'homework_id'    => $homeworkId,
            'submit_content' => $content,
            'submit_file'    => $filePath,
            'submit_time'    => now(),
        ]);

        $this->logBusiness('用户提交作业', [
            'user_id'     => $userId,
            'homework_id' => $homeworkId,
            'submit_id'   => $submit->submit_id,
        ]);

        return [
            'submitId'   => $submit->submit_id,
            'submitTime' => $submit->submit_time,
        ];
    }

}
