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
     *
     * 校验手机号已注册且状态正常 → 生成6位验证码 → 入库（5分钟有效）→ 记入日志
     */
    public function sendCode(string $phone): void
    {
        $user = SysUser::where('phone', $phone)->first();

        if (!$user) {
            throw new BusinessException('该手机号未注册', ResponseCode::DATA_NOT_FOUND);
        }

        if ($user->status !== 1) {
            throw new BusinessException('账号已被禁用，无法重置密码', ResponseCode::ACCOUNT_DISABLED);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        VerifyCode::create([
            'target'      => $phone,
            'code'        => $code,
            'type'        => 1, // 1=用户重置密码
            'expire_time' => now()->addMinutes(5),
            'create_time' => now(),
        ]);

        $this->logBusiness('发送重置密码验证码', [
            'phone' => $phone,
            'code'  => $code,
        ]);

        // TODO: 接入短信服务商，生产环境移除日志中的验证码明文
    }

    /**
     * 重置密码
     *
     * 验证码校验 → 更新密码 → 删除已用验证码
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

        // 删除已使用的验证码，防止重复使用
        $verifyCode->delete();

        $this->logBusiness('用户重置密码成功', ['user_id' => $user->user_id]);
    }
}
