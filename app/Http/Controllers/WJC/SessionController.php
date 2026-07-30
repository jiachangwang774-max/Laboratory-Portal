<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\SessionCreateRequest;
use App\Http\Requests\WJC\SessionUpdateRequest;
use App\Http\Requests\WJC\SessionListRequest;
use App\Services\WJC\SessionService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    public function __construct(private SessionService $service) {}

    public function create(SessionCreateRequest $r): JsonResponse
    {
        $adminId = auth('admin_api')->user()->admin_id;
        return Result::success('课程安排发布成功', $this->service->create($adminId, $r->validated()));
    }

    public function update(SessionUpdateRequest $r, int $sessionId): JsonResponse
    {
        return Result::success('更新成功', $this->service->update($sessionId, $r->validated()));
    }

    public function delete(int $sessionId): JsonResponse
    {
        $this->service->delete($sessionId);
        return Result::success('删除成功');
    }

    public function index(SessionListRequest $r): JsonResponse
    {
        return Result::success('成功', $this->service->list(
            (int) $r->input('page', 1), (int) $r->input('size', 10),
            $r->input('courseId') ? (int) $r->input('courseId') : null
        ));
    }

    public function detail(int $sessionId): JsonResponse
    {
        return Result::success('成功', $this->service->detail($sessionId));
    }
}
