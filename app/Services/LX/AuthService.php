<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Helpers\PhoneHelper;
use App\Models\SysPasswordHistory;
use App\Models\SysUser;
use App\Traits\LogTrait;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthService
{
    use LogTrait;

    /**
     * 用户登录
     *
     * 校验用户名密码 → 检查账号状态 → 签发 accessToken
     */
    public function login(string $username, string $password): array
    {
        $credentials = ['username' => $username, 'password' => $password];

        try {
            $accessToken = auth('user_api')->attempt($credentials);
        } catch (JWTException $e) {
            $this->logException('JWT 令牌签发异常', $e, ['username' => $username]);
            throw new BusinessException('登录失败，请稍后重试', ResponseCode::SYSTEM_ERROR);
        }

        if (!$accessToken) {
            $this->logBusiness('用户登录失败-密码错误', ['username' => $username]);
            throw new BusinessException('用户名或密码错误', ResponseCode::PASSWORD_ERROR);
        }

        /** @var SysUser $user */
        $user = auth('user_api')->user();

        if ($user->status !== 1) {
            auth('user_api')->logout();
            $this->logBusiness('禁用账号尝试登录', [
                'user_id'  => $user->user_id,
                'username' => $user->username,
            ]);
            throw new BusinessException('账号已被禁用，请联系管理员', ResponseCode::ACCOUNT_DISABLED);
        }

        $this->logBusiness('用户登录成功', [
            'user_id'  => $user->user_id,
            'username' => $user->username,
        ]);

        return [
            'accessToken'  => $accessToken,
            'userInfo'     => $this->formatUser($user),
        ];
    }

    /**
     * 用户登出
     *
     * 将当前 accessToken 加入黑名单
     */
    public function logout(): void
    {
        /** @var SysUser|null $user */
        $user = auth('user_api')->user();

        if ($user) {
            $this->logBusiness('用户登出', ['user_id' => $user->user_id]);
        }

        auth('user_api')->logout();
    }

    /**
     * 获取当前登录用户信息
     */
    public function info(): array
    {
        /** @var SysUser $user */
        $user = auth('user_api')->user();

        return $this->formatUser($user);
    }

    /**
     * 修改个人资料
     *
     * 仅更新传入的非空字段，记录变更前后快照
     */
    public function updateInfo(array $data): array
    {
        /** @var SysUser $user */
        $user = auth('user_api')->user();

        $updateData = [];
        if (isset($data['realName'])) {
            $updateData['real_name'] = $data['realName'];
        }
        if (isset($data['avatar'])) {
            $updateData['avatar'] = $data['avatar'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }

        if (empty($updateData)) {
            return $this->formatUser($user);
        }

        $before = $user->only(array_keys($updateData));
        $user->update($updateData);
        $user->refresh();

        $this->logAudit(
            '用户修改个人资料',
            $before,
            $user->only(array_keys($updateData)),
            ['user_id' => $user->user_id]
        );

        return $this->formatUser($user);
    }

    /**
     * 修改密码
     *
     * 校验旧密码 → 检查历史复用 → 更新为新密码 → 记录历史
     */
    public function updatePwd(string $oldPwd, string $newPwd): void
    {
        /** @var SysUser $user */
        $user = auth('user_api')->user();

        if (!Hash::check($oldPwd, $user->password)) {
            throw new BusinessException('原密码错误', ResponseCode::PASSWORD_ERROR);
        }

        // 检查新密码是否与历史密码重复
        $this->checkPasswordHistory($user->user_id, $newPwd);

        $newHash = Hash::make($newPwd);
        $user->password = $newHash;
        $user->save();

        // 记录密码历史
        $this->recordPasswordHistory($user->user_id, $newHash);

        $this->logBusiness('用户修改密码', ['user_id' => $user->user_id]);
    }

    /**
     * 格式化用户信息输出
     */
    private function formatUser(SysUser $user): array
    {
        return [
            'userId'   => $user->user_id,
            'username' => $user->username,
            'realName' => $user->real_name,
            'avatar'   => $user->avatar,
            'phone'    => PhoneHelper::mask($user->phone),
            'email'    => $user->email,
            'grade'    => $user->grade,
            'major'    => $user->major,
        ];
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
