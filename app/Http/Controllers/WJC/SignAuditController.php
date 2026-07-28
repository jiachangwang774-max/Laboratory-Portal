<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\SignListRequest;
use App\Http\Requests\WJC\SingleAuditRequest;
use App\Http\Requests\WJC\BatchAuditRequest;
use App\Services\WJC\SignAuditService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class SignAuditController extends Controller
{
    public function __construct(
        private SignAuditService $signAuditService
    ) {}

    /**
     * 获取报名分页列表
     * GET /api/v1/admin/sign_audit/list
     */
    public function list(SignListRequest $request): JsonResponse
    {
        $data = $this->signAuditService->list(
            (int) $request->input('page', 1),
            (int) $request->input('size', 10),
            $request->input('auditStatus') !== null ? (int) $request->input('auditStatus') : null,
            $request->input('courseId') ? (int) $request->input('courseId') : null
        );
        return Result::success('成功', $data);
    }

    /**
     * 获取报名详情
     * GET /api/v1/admin/sign_audit/detail/{signId}
     */
    public function detail(int $signId): JsonResponse
    {
        $data = $this->signAuditService->detail($signId);
        return Result::success('成功', $data);
    }

    /**
     * 单条审核报名
     * PUT /api/v1/admin/sign_audit/single_audit/{signId}
     */
    public function singleAudit(SingleAuditRequest $request, int $signId): JsonResponse
    {
        $adminId = auth('admin_api')->user()->admin_id;
        $data = $this->signAuditService->singleAudit(
            $signId,
            (int) $request->validated('auditStatus'),
            $request->validated('auditRemark'),
            $adminId
        );
        return Result::success('单条审核操作完成', $data);
    }

    /**
     * 批量审核报名
     * POST /api/v1/admin/sign_audit/batch_audit
     */
    public function batchAudit(BatchAuditRequest $request): JsonResponse
    {
        $adminId = auth('admin_api')->user()->admin_id;
        $data = $this->signAuditService->batchAudit(
            $request->validated('signIdList'),
            (int) $request->validated('auditStatus'),
            $request->validated('auditRemark'),
            $adminId
        );
        return Result::success('批量审核完成', $data);
    }
}
