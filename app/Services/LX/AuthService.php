<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\SysUser;
use App\Traits\LogTrait;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    use LogTrait;

    /**
     * 用户登录：校验凭据，签发双 Token
     */
    public function login(string $username, string $password): array
    {
        $credentials = ['username' => $username, 'password' => $password];

        if (!$accessToken = auth('user_api')->attempt($credentials)) {
            $this->logBusiness('用户登录失败', ['username' => $username]);
            throw new BusinessException('用户名或密码错误', ResponseCode::PASSWORD_ERROR);
        }

        /** @var SysUser $user */
        $user = auth('user_api')->user();

        if ($user->status !== 1) {
            auth('user_api')->logout();
            $this->logBusiness('禁用账号尝试登录', ['user_id' => $user->user_id]);
            throw new BusinessException('账号已被禁用，请联系管理员', ResponseCode::ACCOUNT_DISABLED);
        }

        // 签发 refresh token（长时效）
        $refreshToken = JWTAuth::guard('user_api')
            ->claims(['token_type' => 'refresh'])
            ->setTTL(config('jwt.refresh_token_ttl', 20160))
            ->fromUser($user);

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
     * 用户登出：销毁 accessToken
     */
    public function logout(): void
    {
        $user = auth('user_api')->user();
        if ($user) {
            $this->logBusiness('用户登出', ['user_id' => $user->user_id]);
        }
        auth('user_api')->logout();
    }

    /**
     * 获取当前用户信息
     */
    public function info(): array
    {
        /** @var SysUser $user */
        $user = auth('user_api')->user();
        return $this->formatUser($user);
    }

    /**
     * 修改个人资料
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

        if (!empty($updateData)) {
            $before = $user->only(array_keys($updateData));
            $user->update($updateData);
            $user->refresh();
            $this->logAudit('用户修改个人资料', $before, $user->only(array_keys($updateData)), [
                'user_id' => $user->user_id,
            ]);
        }

        return $this->formatUser($user);
    }

    /**
     * 修改密码
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
     */
    public function refreshToken(string $refreshToken): string
    {
        try {
            $jwt = JWTAuth::guard('user_api')->setToken($refreshToken);

            $payload = $jwt->getPayload();
            if ($payload->get('token_type') !== 'refresh') {
                throw new BusinessException('无效的刷新令牌类型', ResponseCode::TOKEN_INVALID);
            }

            /** @var SysUser $user */
            $user = $jwt->authenticate();

            if ($user->status !== 1) {
                throw new BusinessException('账号已被禁用', ResponseCode::ACCOUNT_DISABLED);
            }

            // 作废旧 refresh token
            $jwt->invalidate();

            // 签发新 access token
            return JWTAuth::guard('user_api')
                ->claims(['token_type' => 'access'])
                ->fromUser($user);

        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BusinessException('刷新令牌无效', ResponseCode::TOKEN_INVALID);
        }
    }

    /**
     * 格式化用户信息
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
