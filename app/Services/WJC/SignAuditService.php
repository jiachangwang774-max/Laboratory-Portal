<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\SignApplication;
use App\Traits\LogTrait;

class SignAuditService
{
    use LogTrait;

    /**
     * 报名申请分页列表（仅已提交 status=1）
     */
    public function list(int $page = 1, int $size = 10, ?int $auditStatus = null): array
    {
        $query = SignApplication::where('status', 1)
            ->orderBy('submit_time', 'desc');

        if ($auditStatus !== null) {
            $query->where('audit_status', $auditStatus);
        }

        $total = $query->count();
        $list = $query->forPage($page, $size)->get()->map(function (SignApplication $app) {
            return [
                'id'              => $app->id,
                'name'            => $app->name,
                'studentId'       => $app->student_id,
                'department'      => $app->department,
                'college'         => $app->college,
                'major'           => $app->major,
                'className'       => $app->class_name,
                'phone'           => $app->phone,
                'auditStatus'     => $app->audit_status,
                'auditRemark'     => $app->audit_remark,
                'auditTime'       => $app->audit_time,
                'submitTime'      => $app->submit_time,
            ];
        });

        return ['total' => $total, 'list' => $list->values()];
    }

    /**
     * 报名申请详情
     */
    public function detail(int $id): array
    {
        $app = SignApplication::where('status', 1)->find($id);

        if (!$app) {
            throw new BusinessException('报名申请不存在', ResponseCode::DATA_NOT_FOUND);
        }

        return [
            'id'               => $app->id,
            'name'             => $app->name,
            'studentId'        => $app->student_id,
            'department'       => $app->department,
            'departmentText'   => $app->department == 1 ? '软件开发实验室' : '人工智能实验室',
            'college'          => $app->college,
            'major'            => $app->major,
            'className'        => $app->class_name,
            'phone'            => $app->phone,
            'selfIntroduction' => $app->self_introduction,
            'auditStatus'      => $app->audit_status,
            'auditRemark'      => $app->audit_remark,
            'auditAdmin'       => $app->admin->real_name ?? '',
            'auditTime'        => $app->audit_time,
            'submitTime'       => $app->submit_time,
        ];
    }

    /**
     * 审核通过
     */
    public function approve(int $id): array
    {
        $app = SignApplication::where('status', 1)->find($id);

        if (!$app) {
            throw new BusinessException('报名申请不存在', ResponseCode::DATA_NOT_FOUND);
        }

        if ($app->audit_status === 1) {
            throw new BusinessException('该申请已审核通过', ResponseCode::DUPLICATE_SUBMIT);
        }

        $adminId = auth('admin_api')->user()->admin_id;

        $app->audit_status = 1;
        $app->audit_admin  = $adminId;
        $app->audit_time   = now();
        $app->save();

        $this->logBusiness('管理员审核通过报名申请', [
            'admin_id'    => $adminId,
            'student_id'  => $app->student_id,
            'application_id' => $id,
        ]);

        return [
            'id'          => $app->id,
            'auditStatus' => $app->audit_status,
            'auditTime'   => $app->audit_time,
        ];
    }

    /**
     * 审核驳回
     */
    public function reject(int $id, ?string $remark = null): array
    {
        $app = SignApplication::where('status', 1)->find($id);

        if (!$app) {
            throw new BusinessException('报名申请不存在', ResponseCode::DATA_NOT_FOUND);
        }

        if ($app->audit_status === 2) {
            throw new BusinessException('该申请已被驳回', ResponseCode::DUPLICATE_SUBMIT);
        }

        $adminId = auth('admin_api')->user()->admin_id;

        $app->audit_status = 2;
        $app->audit_admin  = $adminId;
        $app->audit_remark = $remark;
        $app->audit_time   = now();
        $app->save();

        $this->logBusiness('管理员驳回报名申请', [
            'admin_id'    => $adminId,
            'student_id'  => $app->student_id,
            'application_id' => $id,
            'remark'      => $remark,
        ]);

        return [
            'id'          => $app->id,
            'auditStatus' => $app->audit_status,
            'auditRemark' => $app->audit_remark,
            'auditTime'   => $app->audit_time,
        ];
    }
}
