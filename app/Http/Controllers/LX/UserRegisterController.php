<?php

namespace App\Http\Controllers\LX;

use App\Enums\VerifyCodeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\LX\DeleteAccountRequest;
use App\Http\Requests\LX\RegisterRequest;
use App\Http\Requests\LX\SendCodeRequest;
use App\Services\LX\RegisterService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class UserRegisterController extends Controller
{
    public function __construct(
        private RegisterService $registerService
    ) {}

    /**
     * 统一发送验证码（注册 / 重置密码 / 注销账号）
     * POST /api/v1/user/verify_code/send
     */
    public function sendCode(SendCodeRequest $request): JsonResponse
    {
        $type  = VerifyCodeType::from($request->validated('type'));
        $email = $request->validated('email');
        $this->registerService->sendCode($email, $type);
        return Result::success('验证码已发送，5分钟内有效');
    }

    /**
     * 用户注册
     * POST /api/v1/user/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $this->registerService->register($data);
        return Result::success('注册成功', $user);
    }

    /**
     * 注销账号（需认证）
     * POST /api/v1/user/register/delete_account
     */
    public function deleteAccount(DeleteAccountRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->registerService->deleteAccount($data['email'], $data['code']);
        return Result::success('账号已注销');
    }
}
