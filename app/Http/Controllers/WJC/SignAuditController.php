<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
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
     * 报名列表
     * GET /api/v1/admin/sign/list
     */
    public function index(SignListRequest $request): JsonResponse
    {
        $data = $this->signService->list(
            (int) $request->input('page', 1),
            (int) $request->input('size', 10),
            $request->input('courseId') ? (int) $request->input('courseId') : null
        );
        return Result::success('成功', $data);
    }

    /**
     * 报名详情
     * GET /api/v1/admin/sign/detail/{signId}
     */
    public function detail(int $signId): JsonResponse
    {
        $data = $this->signService->detail($signId);
        return Result::success('成功', $data);
    }

    /**
     * 取消报名
     * PUT /api/v1/admin/sign/cancel/{signId}
     */
    public function cancel(int $signId): JsonResponse
    {
        $data = $this->signService->cancel($signId);
        return Result::success('报名已取消', $data);
    }

    /**
     * 导出报名表
     * GET /api/v1/admin/sign/export
     */
    public function export(Request $request): JsonResponse
    {
        $courseId = $request->input('courseId') ? (int) $request->input('courseId') : null;
        $data = $this->signService->export($courseId);
        return Result::success('成功', ['list' => $data]);
    }
}
