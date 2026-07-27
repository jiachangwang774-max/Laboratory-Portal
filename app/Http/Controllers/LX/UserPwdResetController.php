<?php

namespace App\Http\Controllers\LX;

use App\Http\Controllers\Controller;
use App\Http\Requests\LX\ResetPwdRequest;
use App\Services\LX\PwdResetService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class UserPwdResetController extends Controller
{
    public function __construct(
        private PwdResetService $pwdResetService
    ) {}

    /**
     * 重置密码
     * POST /api/v1/user/pwd_reset/reset_pwd
     */
    public function resetPwd(ResetPwdRequest $request): JsonResponse
    {
        $this->pwdResetService->resetPwd(
            $request->validated('email'),
            $request->validated('code'),
            $request->validated('newPwd')
        );
        return Result::success('密码重置成功，请使用新密码登录');
    }
}
