<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\TrainSign;
use App\Traits\LogTrait;

class SignAuditService
{
    use LogTrait;

    /**
     * 报名分页列表
     */
    public function list(int $page = 1, int $size = 10, ?int $courseId = null): array
    {
        $query = TrainSign::with(['user', 'course'])
            ->orderBy('sign_time', 'desc');

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $total = $query->count();
        $list = $query->forPage($page, $size)->get()->map(function (TrainSign $sign) {
            return [
                'signId'      => $sign->sign_id,
                'userId'      => $sign->user_id,
                'realName'    => $sign->user->real_name ?? '',
                'studentId'   => $sign->user->student_id ?? '',
                'college'     => $sign->user->college ?? '',
                'major'       => $sign->user->major ?? '',
                'courseId'    => $sign->course_id,
                'courseName'  => $sign->course->course_name ?? '',
                'signInfo'    => $sign->sign_info,
                'status'      => $sign->status,
                'signTime'    => $sign->sign_time,
            ];
        });

        return ['total' => $total, 'list' => $list->values()];
    }

    /**
     * 报名详情
     */
    public function detail(int $signId): array
    {
        $sign = TrainSign::with(['user', 'course'])->find($signId);

        if (!$sign) {
            throw new BusinessException('报名记录不存在', ResponseCode::DATA_NOT_FOUND);
        }

        return [
            'signId'       => $sign->sign_id,
            'userRealName' => $sign->user->real_name ?? '',
            'userPhone'    => $sign->user->phone ?? '',
            'userEmail'    => $sign->user->email ?? '',
            'studentId'    => $sign->user->student_id ?? '',
            'college'      => $sign->user->college ?? '',
            'major'        => $sign->user->major ?? '',
            'courseName'   => $sign->course->course_name ?? '',
            'signInfo'     => $sign->sign_info,
            'status'       => $sign->status,
            'signTime'     => $sign->sign_time,
        ];
    }

    /**
     * 取消报名
     */
    public function cancel(int $signId): array
    {
        $sign = TrainSign::find($signId);

        if (!$sign) {
            throw new BusinessException('报名记录不存在', ResponseCode::DATA_NOT_FOUND);
        }

        if ($sign->status === 0) {
            throw new BusinessException('该报名已被取消', ResponseCode::DUPLICATE_SUBMIT);
        }

        $adminId = auth('admin_api')->user()->admin_id;

        $sign->status = 0;
        $sign->save();

        $this->logBusiness('管理员取消报名', [
            'admin_id' => $adminId,
            'sign_id'  => $signId,
        ]);

        return ['signId' => $sign->sign_id, 'status' => 0];
    }

    /**
     * 导出报名表（返回数据供Controller生成Excel）
     */
    public function export(?int $courseId = null): array
    {
        $query = TrainSign::with(['user', 'course'])->where('status', 1);

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        return $query->orderBy('sign_time', 'desc')->get()->map(function (TrainSign $sign) {
            return [
                '姓名'     => $sign->user->real_name ?? '',
                '学号'     => $sign->user->student_id ?? '',
                '学院'     => $sign->user->college ?? '',
                '专业'     => $sign->user->major ?? '',
                '手机号'   => $sign->user->phone ?? '',
                '邮箱'     => $sign->user->email ?? '',
                '课程'     => $sign->course->course_name ?? '',
                '报名信息' => $sign->sign_info ?? '',
                '报名时间' => $sign->sign_time,
            ];
        })->toArray();
    }
}
