<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\SysUser;
use App\Traits\LogTrait;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;

class AuthService
{
    use LogTrait;

    /**
     * 用户登录
     *
     * 校验用户名密码 → 检查账号状态 → 签发 accessToken + refreshToken
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

        // 签发长时效 refresh token（14天）
        try {
            $refreshToken = JWTAuth::guard('user_api')
                ->claims(['token_type' => 'refresh'])
                ->setTTL(config('jwt.refresh_token_ttl', 20160))
                ->fromUser($user);
        } catch (JWTException $e) {
            $this->logException('RefreshToken 签发异常', $e, ['user_id' => $user->user_id]);
            throw new BusinessException('登录失败，请稍后重试', ResponseCode::SYSTEM_ERROR);
        }

        $this->logBusiness('用户登录成功', [
            'user_id'  => $user->user_id,
            'username' => $user->username,
        ]);

        return [
            'accessToken'  => $accessToken,
            'refreshToken' => $refreshToken,
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
     * 校验旧密码 → 更新为新密码
     */
    public function updatePwd(string $oldPwd, string $newPwd): void
    {
        /** @var SysUser $user */
        $user = auth('user_api')->user();

        if (!Hash::check($oldPwd, $user->password)) {
            throw new BusinessException('原密码错误', ResponseCode::PASSWORD_ERROR);
        }

        $user->password = Hash::make($newPwd);
        $user->save();

        $this->logBusiness('用户修改密码', ['user_id' => $user->user_id]);
    }

    /**
     * 刷新 Access Token
     *
     * 用 refreshToken 换取新的 accessToken，同时作废旧 refreshToken
     */
    public function refreshToken(string $refreshTokenStr): string
    {
        try {
            $jwt = JWTAuth::guard('user_api')->setToken($refreshTokenStr);

            // 校验 token 类型必须为 refresh
            $payload = $jwt->getPayload();
            if ($payload->get('token_type') !== 'refresh') {
                throw new BusinessException('无效的刷新令牌类型', ResponseCode::TOKEN_INVALID);
            }

            /** @var SysUser $user */
            $user = $jwt->authenticate();

            if ($user->status !== 1) {
                throw new BusinessException('账号已被禁用', ResponseCode::ACCOUNT_DISABLED);
            }

            // 作废旧 refresh token，防止重复使用
            $jwt->invalidate();

            // 签发新 access token
            return JWTAuth::guard('user_api')
                ->claims(['token_type' => 'access'])
                ->fromUser($user);

        } catch (TokenExpiredException $e) {
            throw new BusinessException('刷新令牌已过期，请重新登录', ResponseCode::LOGIN_EXPIRED);
        } catch (TokenBlacklistedException $e) {
            throw new BusinessException('刷新令牌已失效', ResponseCode::TOKEN_EXPIRED);
        } catch (BusinessException $e) {
            throw $e;
        } catch (JWTException $e) {
            $this->logException('刷新 Token 异常', $e);
            throw new BusinessException('刷新令牌无效', ResponseCode::TOKEN_INVALID);
        }
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
            'phone'    => $user->phone,
            'email'    => $user->email,
        ];
    }
}
