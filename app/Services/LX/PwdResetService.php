<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\SysUser;
use App\Models\VerifyCode;
use App\Traits\LogTrait;
use Illuminate\Support\Facades\Hash;

class PwdResetService
{
    use LogTrait;

    /**
     * 发送重置密码验证码
     */
    public function sendCode(string $phone): void
    {
        $user = SysUser::where('phone', $phone)->where('status', 1)->first();
        if (!$user) {
            throw new BusinessException('该手机号未注册或账号已禁用', ResponseCode::DATA_NOT_FOUND);
        }

        // 生成6位随机验证码
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        VerifyCode::create([
            'target'      => $phone,
            'code'        => $code,
            'type'        => 1,
            'expire_time' => now()->addMinutes(5),
            'create_time' => now(),
        ]);

        // 开发阶段记入日志
        $this->logBusiness('发送重置密码验证码', [
            'phone' => $phone,
            'code'  => $code,
        ]);

        // TODO: 接入短信服务商发送验证码
    }

    /**
     * 重置密码
     */
    public function resetPwd(string $phone, string $code, string $newPwd): void
    {
        $verifyCode = VerifyCode::where('target', $phone)
            ->where('code', $code)
            ->where('type', 1)
            ->where('expire_time', '>', now())
            ->first();

        if (!$verifyCode) {
            throw new BusinessException('验证码错误或已过期', ResponseCode::PARAM_ERROR);
        }

        $user = SysUser::where('phone', $phone)->first();
        if (!$user) {
            throw new BusinessException('用户不存在', ResponseCode::DATA_NOT_FOUND);
        }

        $user->password = Hash::make($newPwd);
        $user->save();

        // 删除已使用的验证码
        $verifyCode->delete();

        $this->logBusiness('用户重置密码成功', ['user_id' => $user->user_id]);
    }
}
