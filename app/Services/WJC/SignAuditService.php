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
                'groupName'       => $app->group_name,
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
            'groupName'        => $app->group_name,
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
        $app->group_name   = $this->assignGroup();
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
     * 按顺序分班：一班→二班→三班 轮流
     */
    private function assignGroup(): string
    {
        $groups = ['一班', '二班', '三班'];
        $last = SignApplication::where('audit_status', 1)
            ->whereNotNull('group_name')
            ->orderBy('audit_time', 'desc')
            ->value('group_name');
        $idx = $last ? (array_search($last, $groups) + 1) % 3 : 0;
        return $groups[$idx];
    }

    /**
     * 重新分班：按审核时间顺序 一班→二班→三班 重新分配
     */
    public function regroup(): array
    {
        $apps = SignApplication::where('audit_status', 1)->orderBy('audit_time')->get();
        $groups = ['一班', '二班', '三班'];
        $result = [];
        foreach ($apps as $i => $app) {
            $g = $groups[$i % 3];
            $app->group_name = $g;
            $app->save();
            $result[] = ['id' => $app->id, 'name' => $app->name, 'groupName' => $g];
        }
        $this->logBusiness('管理员重新分班', ['total' => count($apps)]);
        return ['count' => count($apps), 'groups' => 3, 'list' => $result];
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
