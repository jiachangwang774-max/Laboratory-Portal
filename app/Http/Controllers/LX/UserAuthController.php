<?php

namespace App\Http\Controllers\LX;

use App\Http\Controllers\Controller;
use App\Http\Requests\LX\LoginRequest;
use App\Http\Requests\LX\UpdateInfoRequest;
use App\Http\Requests\LX\UpdatePwdRequest;
use App\Services\LX\AuthService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class UserAuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * 用户登录
     * POST /api/v1/user/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('username'),
            $request->validated('password')
        );
        return Result::success('登录成功', $result);
    }

    /**
     * 用户登出
     * POST /api/v1/user/auth/logout
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();
        return Result::success('退出登录成功');
    }

    /**
     * 获取当前用户信息
     * GET /api/v1/user/auth/info
     */
    public function info(): JsonResponse
    {
        $data = $this->authService->info();
        return Result::success('成功', $data);
    }

    /**
     * 修改个人资料
     * POST /api/v1/user/auth/update_info
     */
    public function updateInfo(UpdateInfoRequest $request): JsonResponse
    {
        $data = $this->authService->updateInfo($request->validated());
        return Result::success('资料修改成功', $data);
    }

    /**
     * 修改密码
     * POST /api/v1/user/auth/update_pwd
     */
    public function updatePwd(UpdatePwdRequest $request): JsonResponse
    {
        $this->authService->updatePwd(
            $request->validated('code'),
            $request->validated('newPwd')
        );
        return Result::success('密码修改成功，请重新登录');
    }

}
