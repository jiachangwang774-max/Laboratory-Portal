<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Enums\VerifyCodeType;
use App\Exceptions\BusinessException;
use App\Mail\VerificationCodeMail;
use App\Models\SysPasswordHistory;
use App\Models\SysUser;
use App\Models\VerifyCode;
use App\Traits\LogTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RegisterService
{
    use LogTrait;

    /**
     * 统一发送验证码（均通过 QQ 邮箱）
     *
     * REGISTER:      校验邮箱未被注册 → 生成6位验证码 → 入库 → 发送邮件
     * PWD_RESET:     校验邮箱已注册且状态正常 → 生成6位验证码 → 入库 → 发送邮件
     * DELETE_ACCOUNT: 校验邮箱已注册 → 生成6位验证码 → 入库 → 发送邮件
     */
    public function sendCode(string $email, VerifyCodeType $type): void
    {
        // 根据类型做前置校验
        match ($type) {
            VerifyCodeType::REGISTER       => $this->validateRegisterTarget($email),
            VerifyCodeType::PWD_RESET      => $this->validatePwdResetTarget($email),
            VerifyCodeType::DELETE_ACCOUNT => $this->validateDeleteAccountTarget($email),
        };

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        VerifyCode::create([
            'target'      => $email,
            'code'        => $code,
            'type'        => $type->value,
            'expire_time' => now()->addMinutes(5),
            'create_time' => now(),
        ]);

        try {
            Mail::to($email)->send(new VerificationCodeMail($code, $type->mailTitle()));
        } catch (\Throwable $e) {
            $this->logException('发送验证码邮件失败', $e, [
                'email' => $email,
                'type'  => $type->label(),
            ]);
            throw new BusinessException('验证码发送失败，请稍后再试', ResponseCode::THIRD_PARTY_ERROR);
        }

        $this->logBusiness('发送验证码', [
            'email' => $email,
            'type'  => $type->label(),
        ]);
    }

    /**
     * 注册场景校验：邮箱未被占用
     */
    private function validateRegisterTarget(string $email): void
    {
        if (SysUser::where('email', $email)->exists()) {
            throw new BusinessException('该邮箱已被注册', ResponseCode::USER_ALREADY_EXISTS);
        }
    }

    /**
     * 重置密码场景校验：邮箱已注册且状态正常
     */
    private function validatePwdResetTarget(string $email): void
    {
        $user = SysUser::where('email', $email)->first();

        if (!$user) {
            throw new BusinessException('该邮箱未注册', ResponseCode::DATA_NOT_FOUND);
        }

        if ($user->status !== 1) {
            throw new BusinessException('账号已被禁用，无法重置密码', ResponseCode::ACCOUNT_DISABLED);
        }
    }

    /**
     * 注销账号场景校验：邮箱已注册
     */
    private function validateDeleteAccountTarget(string $email): void
    {
        if (!SysUser::where('email', $email)->exists()) {
            throw new BusinessException('该邮箱未注册', ResponseCode::DATA_NOT_FOUND);
        }
    }

    /**
     * 用户注册
     *
     * 验证码校验 → 检查用户名/邮箱唯一性 → 创建用户 → 删除已用验证码
     */
    public function register(array $data): array
    {
        $verifyCode = VerifyCode::where('target', $data['email'])
            ->where('code', $data['code'])
            ->where('type', VerifyCodeType::REGISTER->value)
            ->where('expire_time', '>', now())
            ->first();

        if (!$verifyCode) {
            throw new BusinessException('验证码错误或已过期', ResponseCode::VERIFY_CODE_ERROR);
        }

        // 检查用户名唯一性
        if (SysUser::where('username', $data['username'])->exists()) {
            throw new BusinessException('用户名已被占用', ResponseCode::USER_ALREADY_EXISTS);
        }

        // 检查邮箱唯一性（防止并发绕过 sendCode 检查）
        if (SysUser::where('email', $data['email'])->exists()) {
            throw new BusinessException('该邮箱已被注册', ResponseCode::USER_ALREADY_EXISTS);
        }

        $user = SysUser::create([
            'username'   => $data['username'],
            'password'   => Hash::make($data['password']),
            'email'      => $data['email'],
            'grade'      => $data['grade'],
            'major'      => $data['major'],
            'college'    => $data['college'],
            'student_id' => $data['student_id'],
            'status'     => 1,
        ]);

        // 记录初始密码到历史
        SysPasswordHistory::create([
            'user_id'       => $user->user_id,
            'password_hash' => $user->password,
            'create_time'   => now(),
        ]);

        // 删除已使用的验证码，防止重复使用
        $verifyCode->delete();

        $this->logBusiness('用户注册成功', [
            'user_id'  => $user->user_id,
            'username' => $user->username,
            'email'    => $user->email,
        ]);

        return [
            'userId'     => $user->user_id,
            'username'   => $user->username,
            'email'      => $user->email,
            'grade'      => $user->grade,
            'major'      => $user->major,
            'college'    => $user->college,
            'student_id' => $user->student_id,
        ];
    }

    /**
     * 注销账号
     *
     * 验证码校验 → 查找用户 → 删除用户 → 删除已用验证码
     */
    public function deleteAccount(string $email, string $code): void
    {
        $verifyCode = VerifyCode::where('target', $email)
            ->where('code', $code)
            ->where('type', VerifyCodeType::DELETE_ACCOUNT->value)
            ->where('expire_time', '>', now())
            ->first();

        if (!$verifyCode) {
            throw new BusinessException('验证码错误或已过期', ResponseCode::VERIFY_CODE_ERROR);
        }

        $user = SysUser::where('email', $email)->first();

        if (!$user) {
            throw new BusinessException('用户不存在', ResponseCode::DATA_NOT_FOUND);
        }

        $userId   = $user->user_id;
        $username = $user->username;

        $user->delete();

        // 删除已使用的验证码，防止重复使用
        $verifyCode->delete();

        $this->logBusiness('用户注销账号成功', [
            'user_id'  => $userId,
            'username' => $username,
            'email'    => $email,
        ]);
    }
}
