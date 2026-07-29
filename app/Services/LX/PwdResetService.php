<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Enums\VerifyCodeType;
use App\Exceptions\BusinessException;
use App\Models\SysPasswordHistory;
use App\Models\SysUser;
use App\Models\VerifyCode;
use App\Traits\LogTrait;
use Illuminate\Support\Facades\Hash;

class PwdResetService
{
    use LogTrait;

    /**
     * 重置密码
     *
     * 学号+邮箱校验身份 → 验证码校验 → 检查历史复用 → 更新密码 → 记录历史 → 删除已用验证码
     */
    public function resetPwd(string $studentId, string $email, string $code, string $newPwd): void
    {
        // 通过学号和邮箱双重校验确认用户身份
        $user = SysUser::where('student_id', $studentId)
            ->where('email', $email)
            ->first();

        if (!$user) {
            throw new BusinessException('学号与邮箱不匹配，用户不存在', ResponseCode::DATA_NOT_FOUND);
        }

        $verifyCode = VerifyCode::where('target', $email)
            ->where('code', $code)
            ->where('type', VerifyCodeType::PWD_RESET->value)
            ->where('expire_time', '>', now())
            ->first();

        if (!$verifyCode) {
            throw new BusinessException('验证码错误或已过期', ResponseCode::PARAM_ERROR);
        }

        // 检查新密码是否与历史密码重复
        $this->checkPasswordHistory($user->user_id, $newPwd);

        $newHash = Hash::make($newPwd);
        $user->password = $newHash;
        $user->save();

        // 记录密码历史
        $this->recordPasswordHistory($user->user_id, $newHash);

        // 删除已使用的验证码，防止重复使用
        $verifyCode->delete();

        $this->logBusiness('用户重置密码成功', ['user_id' => $user->user_id]);
    }

    /**
     * 检查新密码是否与历史密码重复。
     *
     * 取最近 N 条历史记录逐一比对，命中则抛出异常。
     */
    private function checkPasswordHistory(int $userId, string $newPwd): void
    {
        $limit = config('password.history_limit', 5);

        $histories = SysPasswordHistory::where('user_id', $userId)
            ->orderBy('create_time', 'desc')
            ->limit($limit)
            ->get();

        foreach ($histories as $history) {
            if (Hash::check($newPwd, $history->password_hash)) {
                throw new BusinessException(
                    "新密码不能与近期使用过的 {$limit} 次密码相同",
                    ResponseCode::BUSINESS_ERROR,
                );
            }
        }
    }

    /**
     * 记录密码到历史表，并仅保留最近 N 条。
     */
    private function recordPasswordHistory(int $userId, string $hash): void
    {
        SysPasswordHistory::create([
            'user_id'       => $userId,
            'password_hash' => $hash,
            'create_time'   => now(),
        ]);

        // 仅保留最近 N 条，删除多余的旧记录
        $limit = config('password.history_limit', 5);

        $idsToKeep = SysPasswordHistory::where('user_id', $userId)
            ->orderBy('create_time', 'desc')
            ->limit($limit)
            ->pluck('id');

        SysPasswordHistory::where('user_id', $userId)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}
