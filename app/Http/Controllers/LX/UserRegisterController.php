<?php

namespace App\Http\Controllers\LX;

use App\Enums\VerifyCodeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\LX\DeleteAccountRequest;
use App\Http\Requests\LX\RegisterRequest;
use App\Http\Requests\LX\SendRegisterCodeRequest;
use App\Services\LX\RegisterService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class UserRegisterController extends Controller
{
    public function __construct(
        private RegisterService $registerService
    ) {}

    /**
     * 发送验证码到 QQ 邮箱（注册/注销账号）
     * POST /api/v1/user/register/send_code
     */
    public function sendCode(SendRegisterCodeRequest $request): JsonResponse
    {
        $type = VerifyCodeType::from($request->validated('type'));
        $this->registerService->sendCode($request->validated('email'), $type);
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
