<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\NoticeCreateRequest;
use App\Http\Requests\WJC\NoticeUpdateRequest;
use App\Http\Requests\WJC\NoticeListRequest;
use App\Services\WJC\NoticeService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class NoticeController extends Controller
{
    public function __construct(
        private NoticeService $noticeService
    ) {}

    /**
     * 创建公告
     * POST /api/v1/admin/notice/create
     */
    public function create(NoticeCreateRequest $request): JsonResponse
    {
        $adminId = auth('admin_api')->user()->admin_id;
        $data = $this->noticeService->create($adminId, $request->validated());
        return Result::success('公告发布成功', $data);
    }

    /**
     * 删除公告
     * DELETE /api/v1/admin/notice/delete/{noticeId}
     */
    public function delete(int $noticeId): JsonResponse
    {
        $this->noticeService->delete($noticeId);
        return Result::success('公告删除成功');
    }

    /**
     * 更新公告
     * PUT /api/v1/admin/notice/update/{noticeId}
     */
    public function update(NoticeUpdateRequest $request, int $noticeId): JsonResponse
    {
        $data = $this->noticeService->update($noticeId, $request->validated());
        return Result::success('公告编辑成功', $data);
    }

    /**
     * 后台获取公告列表
     * GET /api/v1/admin/notice/list
     */
    public function index(NoticeListRequest $request): JsonResponse
    {
        $data = $this->noticeService->list(
            (int) $request->input('page', 1),
            (int) $request->input('size', 10),
            $request->input('title')
        );
        return Result::success('成功', $data);
    }

    /**
     * 后台获取公告详情
     * GET /api/v1/admin/notice/detail/{noticeId}
     */
    public function detail(int $noticeId): JsonResponse
    {
        $data = $this->noticeService->detail($noticeId);
        return Result::success('成功', $data);
    }

    /**
     * 前台获取公告列表（公开接口）
     * GET /api/v1/admin/notice/front/list
     */
    public function frontList(NoticeListRequest $request): JsonResponse
    {
        $data = $this->noticeService->frontList(
            (int) $request->input('page', 1),
            (int) $request->input('size', 10)
        );
        return Result::success('成功', $data);
    }

    /**
     * 前台获取公告详情（公开接口）
     * GET /api/v1/admin/notice/front/detail/{noticeId}
     */
    public function frontDetail(int $noticeId): JsonResponse
    {
        $data = $this->noticeService->frontDetail($noticeId);
        return Result::success('成功', $data);
    }
}
