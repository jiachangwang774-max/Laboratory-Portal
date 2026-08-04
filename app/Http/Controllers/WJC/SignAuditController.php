<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\SignAuditRequest;
use App\Http\Requests\WJC\SignListRequest;
use App\Services\WJC\SignAuditService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignAuditController extends Controller
{
    public function __construct(
        private SignAuditService $signService
    ) {}

    /**
     * 报名申请列表
     * GET /api/v1/admin/sign/list
     */
    public function index(SignListRequest $request): JsonResponse
    {
        $data = $this->signService->list(
            (int) $request->input('page', 1),
            (int) $request->input('size', 10),
            $request->input('auditStatus') !== null ? (int) $request->input('auditStatus') : null
        );
        return Result::success('成功', $data);
    }

    /**
     * 报名申请详情
     * GET /api/v1/admin/sign/detail/{id}
     */
    public function detail(int $id): JsonResponse
    {
        $data = $this->signService->detail($id);
        return Result::success('成功', $data);
    }

    /**
     * 审核通过
     * PUT /api/v1/admin/sign/approve/{id}
     */
    public function approve(int $id): JsonResponse
    {
        $data = $this->signService->approve($id);
        return Result::success('审核通过', $data);
    }

    /**
     * 审核驳回
     * PUT /api/v1/admin/sign/reject/{id}
     */
    public function reject(SignAuditRequest $request, int $id): JsonResponse
    {
        $data = $this->signService->reject($id, $request->validated('remark'));
        return Result::success('已驳回', $data);
    }

    /**
     * 重新分班
     * POST /api/v1/admin/sign/regroup
     */
    public function regroup(): JsonResponse
    {
        $data = $this->signService->regroup();
        return Result::success('分班完成', $data);
    }

    /**
     * 培训名单 - 按班级查看学生
     * GET /admin/sign/class/list?groupName=一班
     */
    public function classList(Request $r): JsonResponse
    {
        $groups = [1 => '一班', 2 => '二班', 3 => '三班'];
        $id = (int) $r->input('groupId', 1);
        $data = $this->signService->classList($groups[$id] ?? '一班');
        return Result::success('成功', $data);
    }

    /**
     * 培训名单 - 导出
     * GET /admin/sign/class/export?groupId=1
     */
    public function classExport(Request $r): JsonResponse
    {
        $groups = [1 => '一班', 2 => '二班', 3 => '三班'];
        $id = (int) $r->input('groupId', 1);
        $data = $this->signService->classExport($groups[$id] ?? '一班');
        return Result::success('成功', ['list' => $data]);
    }
}
