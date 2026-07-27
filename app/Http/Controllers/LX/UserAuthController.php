<?php

namespace App\Http\Controllers\LX;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\SysUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;

class UserAuthController extends Controller
{
    /**
     * 用户登录
     * POST /api/v1/user/auth/login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:50',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        $credentials = $request->only(['username', 'password']);

        if (!$accessToken = auth('user_api')->attempt($credentials)) {
            return ApiResponse::unauthorized('用户名或密码错误');
        }

        /** @var SysUser $user */
        $user = auth('user_api')->user();

        // 检查账号状态
        if ($user->status !== 1) {
            auth('user_api')->logout();
            return ApiResponse::forbidden('账号已被禁用，请联系管理员');
        }

        // 签发长时效 refreshToken
        try {
            $refreshToken = JWTAuth::guard('user_api')
                ->claims(['token_type' => 'refresh'])
                ->setTTL(config('jwt.refresh_token_ttl', 20160))
                ->fromUser($user);
        } catch (JWTException $e) {
            return ApiResponse::error('令牌签发失败', 500);
        }

        return ApiResponse::success([
            'accessToken'  => $accessToken,
            'refreshToken' => $refreshToken,
            'userInfo'     => $this->formatUserInfo($user),
        ], '登录成功');
    }

    /**
     * 用户登出
     * POST /api/v1/user/auth/logout
     */
    public function logout()
    {
        auth('user_api')->logout();

        return ApiResponse::success(null, '退出登录成功');
    }

    /**
     * 获取当前用户信息
     * GET /api/v1/user/auth/info
     */
    public function info()
    {
        /** @var SysUser $user */
        $user = auth('user_api')->user();

        return ApiResponse::success($this->formatUserInfo($user));
    }

    /**
     * 修改个人资料
     * POST /api/v1/user/auth/update_info
     */
    public function updateInfo(Request $request)
    {
        /** @var SysUser $user */
        $user = auth('user_api')->user();

        $validator = Validator::make($request->all(), [
            'realName' => 'nullable|string|max:20',
            'avatar'   => 'nullable|string|max:255',
            'email'    => 'nullable|email|max:50',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        $data = [];
        if ($request->has('realName')) {
            $data['real_name'] = $request->input('realName');
        }
        if ($request->has('avatar')) {
            $data['avatar'] = $request->input('avatar');
        }
        if ($request->has('email')) {
            $data['email'] = $request->input('email');
        }

        if (!empty($data)) {
            $user->update($data);
        }

        return ApiResponse::success($this->formatUserInfo($user->fresh()), '资料修改成功');
    }

    /**
     * 修改密码
     * POST /api/v1/user/auth/update_pwd
     */
    public function updatePwd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldPwd' => 'required|string',
            'newPwd' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        /** @var SysUser $user */
        $user = auth('user_api')->user();

        // 验证旧密码
        if (!Hash::check($request->input('oldPwd'), $user->password)) {
            return ApiResponse::error('原密码错误', 422);
        }

        $user->password = Hash::make($request->input('newPwd'));
        $user->save();

        return ApiResponse::success(null, '密码修改成功，请重新登录');
    }

    /**
     * 刷新 Access Token
     * POST /api/v1/user/auth/refresh_token
     */
    public function refreshToken(Request $request)
    {
        $refreshToken = $request->input('refreshToken');

        if (!$refreshToken) {
            return ApiResponse::error('缺少refreshToken参数', 422);
        }

        try {
            $jwt = JWTAuth::guard('user_api')->setToken($refreshToken);

            // 验证这是 refresh 类型的 token
            $payload = $jwt->getPayload();
            if ($payload->get('token_type') !== 'refresh') {
                return ApiResponse::unauthorized('无效的刷新令牌类型');
            }

            // 从 refresh token 中认证用户
            /** @var SysUser $user */
            $user = $jwt->authenticate();

            // 检查账号状态
            if ($user->status !== 1) {
                return ApiResponse::forbidden('账号已被禁用');
            }

            // 作废旧 refresh token
            $jwt->invalidate();

            // 签发新 access token
            $newAccessToken = JWTAuth::guard('user_api')
                ->claims(['token_type' => 'access'])
                ->fromUser($user);

            return ApiResponse::success([
                'accessToken' => $newAccessToken,
            ], '刷新成功');

        } catch (TokenExpiredException $e) {
            return ApiResponse::unauthorized('刷新令牌已过期，请重新登录');
        } catch (TokenBlacklistedException $e) {
            return ApiResponse::unauthorized('刷新令牌已失效');
        } catch (\Exception $e) {
            return ApiResponse::unauthorized('刷新令牌无效');
        }
    }

    /**
     * 格式化用户信息返回字段
     */
    private function formatUserInfo(SysUser $user): array
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
