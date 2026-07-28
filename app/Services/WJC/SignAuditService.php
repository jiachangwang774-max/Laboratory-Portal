<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\TrainSign;
use App\Traits\LogTrait;
use Illuminate\Support\Facades\DB;

class SignAuditService
{
    use LogTrait;

    /**
     * 获取报名分页列表
     */
    public function list(int $page = 1, int $size = 10, ?int $auditStatus = null, ?int $courseId = null): array
    {
        $query = TrainSign::with(['user', 'course'])
            ->orderBy('sign_time', 'desc');

        if ($auditStatus !== null) {
            $query->where('audit_status', $auditStatus);
        }

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $total = $query->count();
        $list  = $query->forPage($page, $size)->get()->map(function (TrainSign $sign) {
            return [
                'signId'      => $sign->sign_id,
                'userId'      => $sign->user_id,
                'realName'    => $sign->user->real_name ?? '',
                'courseId'    => $sign->course_id,
                'courseName'  => $sign->course->course_name ?? '',
                'signInfo'    => $sign->sign_info,
                'auditStatus' => $sign->audit_status,
                'statusText'  => $this->auditStatusText($sign->audit_status),
                'signTime'    => $sign->sign_time,
            ];
        });

        return [
            'total' => $total,
            'list'  => $list->values(),
        ];
    }

    /**
     * 获取报名详情
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
            'courseName'   => $sign->course->course_name ?? '',
            'signInfo'     => $sign->sign_info,
            'auditStatus'  => $sign->audit_status,
            'auditRemark'  => $sign->audit_remark,
            'signTime'     => $sign->sign_time,
        ];
    }

    /**
     * 单条审核报名
     */
    public function singleAudit(int $signId, int $auditStatus, ?string $auditRemark, int $adminId): array
    {
        $sign = TrainSign::find($signId);

        if (!$sign) {
            throw new BusinessException('报名记录不存在', ResponseCode::DATA_NOT_FOUND);
        }

        if ($sign->audit_status !== 0) {
            throw new BusinessException('该报名记录已审核，请勿重复操作', ResponseCode::DUPLICATE_SUBMIT);
        }

        $sign->audit_status = $auditStatus;
        $sign->audit_admin  = $adminId;
        $sign->audit_remark = $auditRemark;
        $sign->audit_time   = now();
        $sign->save();

        $this->logBusiness('管理员单条审核报名', [
            'admin_id'     => $adminId,
            'sign_id'      => $signId,
            'audit_status' => $auditStatus,
        ]);

        return [
            'signId'      => $sign->sign_id,
            'auditStatus' => $sign->audit_status,
            'statusText'  => $this->auditStatusText($sign->audit_status),
        ];
    }

    /**
     * 批量审核报名
     */
    public function batchAudit(array $signIdList, int $auditStatus, ?string $auditRemark, int $adminId): array
    {
        $successCount = 0;

        // 先查出所有待审核的报名记录
        $signs = TrainSign::whereIn('sign_id', $signIdList)
            ->where('audit_status', 0)
            ->get();

        if ($signs->isEmpty()) {
            throw new BusinessException('没有可审核的报名记录', ResponseCode::DATA_NOT_FOUND);
        }

        foreach ($signs as $sign) {
            $sign->audit_status = $auditStatus;
            $sign->audit_admin  = $adminId;
            $sign->audit_remark = $auditRemark;
            $sign->audit_time   = now();
            $sign->save();
            $successCount++;
        }

        $skippedCount = count($signIdList) - $successCount;

        $this->logBusiness('管理员批量审核报名', [
            'admin_id'      => $adminId,
            'total'         => count($signIdList),
            'success_count' => $successCount,
            'skipped_count' => $skippedCount,
            'audit_status'  => $auditStatus,
        ]);

        return [
            'successCount' => $successCount,
            'skippedCount' => $skippedCount,
        ];
    }

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
