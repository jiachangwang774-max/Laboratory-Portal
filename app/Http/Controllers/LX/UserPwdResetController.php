<?php

namespace App\Http\Controllers\LX;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\SysUser;
use App\Models\VerifyCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserPwdResetController extends Controller
{
    /**
     * 发送重置密码验证码
     * POST /api/v1/user/pwd_reset/send_code
     */
    public function sendCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|size:11',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        $phone = $request->input('phone');

        // 验证手机号是否存在且账号正常
        $user = SysUser::where('phone', $phone)->where('status', 1)->first();
        if (!$user) {
            return ApiResponse::error('该手机号未注册或账号已禁用', 422);
        }

        // 生成6位随机验证码
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // 存储验证码（5分钟有效）
        VerifyCode::create([
            'target'      => $phone,
            'code'        => $code,
            'type'        => 1, // 1=用户重置密码
            'expire_time' => now()->addMinutes(5),
            'create_time' => now(),
        ]);

        // 开发阶段：将验证码写入日志，方便调试
        Log::channel('business')->info('用户重置密码验证码', [
            'phone' => $phone,
            'code'  => $code,
        ]);

        // TODO: 接入短信服务商发送验证码

        return ApiResponse::success(null, '验证码已发送，5分钟内有效');
    }

    /**
     * 重置密码
     * POST /api/v1/user/pwd_reset/reset_pwd
     */
    public function resetPwd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'  => 'required|string|size:11',
            'code'   => 'required|string|size:6',
            'newPwd' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        $phone  = $request->input('phone');
        $code   = $request->input('code');
        $newPwd = $request->input('newPwd');

        // 验证验证码
        $verifyCode = VerifyCode::where('target', $phone)
            ->where('code', $code)
            ->where('type', 1)
            ->where('expire_time', '>', now())
            ->first();

        if (!$verifyCode) {
            return ApiResponse::error('验证码错误或已过期', 422);
        }

        // 更新密码
        $user = SysUser::where('phone', $phone)->first();
        if (!$user) {
            return ApiResponse::error('用户不存在', 422);
        }

        $user->password = Hash::make($newPwd);
        $user->save();

        // 删除已使用的验证码
        $verifyCode->delete();

        return ApiResponse::success(null, '密码重置成功，请使用新密码登录');
    }
}
