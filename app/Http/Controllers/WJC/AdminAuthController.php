<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\AdminLoginRequest;
use App\Http\Requests\WJC\AdminUpdatePwdRequest;
use App\Http\Requests\WJC\AdminSendCodeRequest;
use App\Http\Requests\WJC\AdminResetPwdRequest;
use App\Services\WJC\AdminAuthService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class AdminAuthController extends Controller
{
    public function __construct(
        private AdminAuthService $adminAuthService
    ) {}

    /**
     * 管理员登录
     * POST /api/v1/admin/auth/login
     */
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $result = $this->adminAuthService->login(
            $request->validated('adminName'),
            $request->validated('password')
        );
        return Result::success('登录成功', $result);
    }

    /**
     * 管理员登出
     * POST /api/v1/admin/auth/logout
     */
    public function logout(): JsonResponse
    {
        $this->adminAuthService->logout();
        return Result::success('后台登出成功');
    }

    /**
     * 获取当前管理员信息
     * GET /api/v1/admin/auth/info
     */
    public function info(): JsonResponse
    {
        $data = $this->adminAuthService->info();
        return Result::success('成功', $data);
    }

    /**
     * 管理员修改密码
     * POST /api/v1/admin/auth/update_pwd
     */
    public function updatePwd(AdminUpdatePwdRequest $request): JsonResponse
    {
        $this->adminAuthService->updatePwd(
            $request->validated('oldPwd'),
            $request->validated('newPwd')
        );
        return Result::success('管理员密码修改完成');
    }

    /**
     * 发送管理员找回密码验证码
     * POST /api/v1/admin/auth/send_code
     */
    public function sendCode(AdminSendCodeRequest $request): JsonResponse
    {
        $this->adminAuthService->sendCode($request->validated('adminName'));
        return Result::success('验证码已下发');
    }

    /**
     * 管理员重置密码
     * POST /api/v1/admin/auth/reset_pwd
     */
    public function resetPwd(AdminResetPwdRequest $request): JsonResponse
    {
        $this->adminAuthService->resetPwd(
            $request->validated('adminName'),
            $request->validated('code'),
            $request->validated('newPwd')
        );
        return Result::success('管理员密码重置成功');
    }
}
