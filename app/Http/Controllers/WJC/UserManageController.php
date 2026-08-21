<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\UserListRequest;
use App\Http\Requests\WJC\UserCreateRequest;
use App\Http\Requests\WJC\UserStatusRequest;
use App\Services\WJC\UserManageService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManageController extends Controller
{
    public function __construct(private UserManageService $service) {}

    public function index(UserListRequest $r): JsonResponse
    {
        return Result::success('成功', $this->service->list(
            (int) $r->input('page', 1), (int) $r->input('size', 10),
            $r->input('keyword'), $r->input('status') !== null ? (int) $r->input('status') : null
        ));
    }

    public function detail(Request $r, int $userId): JsonResponse
    {
        return Result::success('成功', $this->service->detail($userId, $r->input('role')));
    }

    public function status(UserStatusRequest $r, int $userId): JsonResponse
    {
        return Result::success('账号状态已更新', $this->service->status($userId, (int) $r->validated('status')));
    }

    public function create(UserCreateRequest $r): JsonResponse
    {
        $data = $this->service->create($r->validated());
        $role = $r->input('role', 'student');
        $msg = $role === 'admin' ? '管理员账号创建成功' : '学员账号创建成功';
        return Result::success($msg.'，默认密码为 Pass@123', $data);
    }

    public function delete(Request $r): JsonResponse
    {
        $this->service->delete((int) $r->input('userId'), $r->input('role'));
        return Result::success('删除成功');
    }
}
