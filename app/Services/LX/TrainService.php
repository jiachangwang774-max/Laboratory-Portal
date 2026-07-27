<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\HomeworkSubmit;
use App\Models\SystemConfig;
use App\Models\TrainCourse;
use App\Models\TrainHomework;
use App\Models\TrainSign;
use App\Traits\LogTrait;
use Illuminate\Support\Facades\DB;

class TrainService
{
    use LogTrait;

    /**
     * 培训课程分页列表
     *
     * 仅返回 status=1 的上架课程，按创建时间倒序
     */
    public function courseList(int $page = 1, int $size = 10): array
    {
        $query = TrainCourse::enabled()->orderBy('create_time', 'desc');

        $total = $query->count();
        $list  = $query->forPage($page, $size)->get()->map(function (TrainCourse $course) {
            return [
                'courseId'   => $course->course_id,
                'courseName' => $course->course_name,
                'courseDesc' => $course->course_desc,
                'coverImg'   => $course->cover_img,
                'startTime'  => $course->start_time,
                'endTime'    => $course->end_time,
                'maxSign'    => $course->max_sign,
            ];
        });

        return [
            'total' => $total,
            'list'  => $list->values(),
        ];
    }

    /**
     * 报名培训课程
     *
     * 校验报名开关 → 校验课程上架 → 校验未重复报名 → 写入报名记录
     */
    public function courseSign(int $userId, int $courseId, ?string $signInfo = null): array
    {
        // 检查全局报名开关
        $switch = SystemConfig::getValue('train_sign_switch', '1');
        if ($switch !== '1') {
            throw new BusinessException('当前报名功能已关闭', ResponseCode::BUSINESS_ERROR);
        }

        // 检查课程是否存在且上架
        $course = TrainCourse::enabled()->find($courseId);
        if (!$course) {
            throw new BusinessException('课程不存在或已下架', ResponseCode::DATA_NOT_FOUND);
        }

        // 检查是否已报名
        $exists = TrainSign::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->exists();

        if ($exists) {
            throw new BusinessException('您已报名该课程，请勿重复报名', ResponseCode::DUPLICATE_SUBMIT);
        }

        $sign = TrainSign::create([
            'user_id'      => $userId,
            'course_id'    => $courseId,
            'sign_info'    => $signInfo,
            'audit_status' => 0, // 待审核
            'sign_time'    => now(),
        ]);

        $this->logBusiness('用户报名课程', [
            'user_id'   => $userId,
            'course_id' => $courseId,
            'sign_id'   => $sign->sign_id,
        ]);

        return [
            'signId'      => $sign->sign_id,
            'auditStatus' => 0,
            'statusText'  => '待审核',
        ];
    }

    /**
     * 我的报名记录分页
     *
     * 关联课程表获取课程名称，按报名时间倒序
     */
    public function signList(int $userId, int $page = 1, int $size = 10): array
    {
        $query = TrainSign::with('course')
            ->where('user_id', $userId)
            ->orderBy('sign_time', 'desc');

        $total = $query->count();
        $list  = $query->forPage($page, $size)->get()->map(function (TrainSign $sign) {
            return [
                'signId'      => $sign->sign_id,
                'courseId'    => $sign->course_id,
                'courseName'  => $sign->course->course_name ?? '',
                'signInfo'    => $sign->sign_info,
                'auditStatus' => $sign->audit_status,
                'statusText'  => $this->auditStatusText($sign->audit_status),
                'auditRemark' => $sign->audit_remark,
                'signTime'    => $sign->sign_time,
                'auditTime'   => $sign->audit_time,
            ];
        });

        return [
            'total' => $total,
            'list'  => $list->values(),
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
            ->where('audit_status', 1)
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

        // 校验用户已通过该课程审核
        $approved = TrainSign::where('user_id', $userId)
            ->where('course_id', $homework->course_id)
            ->where('audit_status', 1)
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

    /**
     * 审核状态 → 文字映射
     */
    private function auditStatusText(int $status): string
    {
        return match ($status) {
            0 => '待审核',
            1 => '审核通过',
            2 => '审核驳回',
            default => '未知',
        };
    }
}
