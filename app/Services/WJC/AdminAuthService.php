<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Enums\VerifyCodeType;
use App\Exceptions\BusinessException;
use App\Helpers\PhoneHelper;
use App\Models\SysAdmin;
use App\Mail\VerificationCodeMail;
use App\Models\VerifyCode;
use App\Traits\LogTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AdminAuthService
{
    use LogTrait;

    /**
     * 管理员登录
     */
    public function login(string $adminName, string $password): array
    {
        $credentials = ['admin_name' => $adminName, 'password' => $password];

        try {
            $accessToken = auth('admin_api')->attempt($credentials);
        } catch (JWTException $e) {
            $this->logException('管理员JWT令牌签发异常', $e, ['admin_name' => $adminName]);
            throw new BusinessException('登录失败，请稍后重试', ResponseCode::SYSTEM_ERROR);
        }

        if (!$accessToken) {
            $this->logLogin('管理员登录', 0, $adminName, 0, '管理员账号或密码错误');
            throw new BusinessException('管理员账号或密码错误', ResponseCode::PASSWORD_ERROR);
        }

        /** @var SysAdmin $admin */
        $admin = auth('admin_api')->user();

        if ($admin->status !== 1) {
            auth('admin_api')->logout();
            $this->logLogin('管理员登录', $admin->admin_id, $admin->admin_name, 0, '管理员账号已被禁用');
            throw new BusinessException('管理员账号已被禁用', ResponseCode::ACCOUNT_DISABLED);
        }

        $this->logLogin('管理员登录', $admin->admin_id, $admin->admin_name, 1);

        return [
            'accessToken' => $accessToken,
            'adminInfo'   => $this->formatAdmin($admin),
        ];
    }

    /**
     * 管理员登出
     */
    public function logout(): void
    {
        /** @var SysAdmin|null $admin */
        $admin = auth('admin_api')->user();

        if ($admin) {
            $this->logBusiness('管理员登出', ['admin_id' => $admin->admin_id]);
        }

        auth('admin_api')->logout();
    }

    /**
     * 获取当前管理员信息
     */
    public function info(): array
    {
        /** @var SysAdmin $admin */
        $admin = auth('admin_api')->user();

        return $this->formatAdmin($admin);
    }

    /**
     * 管理员修改自身密码
     */
    public function updatePwd(string $oldPwd, string $newPwd): void
    {
        /** @var SysAdmin $admin */
        $admin = auth('admin_api')->user();

        if (!Hash::check($oldPwd, $admin->password)) {
            throw new BusinessException('原密码错误', ResponseCode::PASSWORD_ERROR);
        }

        if (Hash::check($newPwd, $admin->password)) {
            throw new BusinessException('新密码不能与当前密码相同', ResponseCode::BUSINESS_ERROR);
        }

        $admin->password = Hash::make($newPwd);
        $admin->save();

        $this->logBusiness('管理员修改密码', ['admin_id' => $admin->admin_id]);
    }

    /**
     * 发送管理员找回密码验证码
     */
    public function sendCode(string $adminName): void
    {
        $admin = SysAdmin::where('admin_name', $adminName)->first();

        if (!$admin) {
            throw new BusinessException('管理员账号不存在', ResponseCode::DATA_NOT_FOUND);
        }

        if (empty($admin->email)) {
            throw new BusinessException('该账号未绑定邮箱，无法找回密码', ResponseCode::BUSINESS_ERROR);
        }

        $code = (string) random_int(100000, 999999);

        VerifyCode::create([
            'target'      => $admin->email,
            'code'        => $code,
            'type'        => VerifyCodeType::ADMIN_PWD_RESET->value,
            'expire_time' => now()->addMinutes(5),
            'create_time' => now(),
        ]);

        Mail::to($admin->email)->send(new VerificationCodeMail($code, '管理员重置密码'));

        $this->logBusiness('管理员找回密码验证码已发送', [
            'admin_id' => $admin->admin_id,
            'email'    => $admin->email,
        ]);
    }

    /**
     * 管理员重置密码
     */
    public function resetPwd(string $adminName, string $code, string $newPwd): void
    {
        $admin = SysAdmin::where('admin_name', $adminName)->first();

        if (!$admin) {
            throw new BusinessException('管理员不存在', ResponseCode::DATA_NOT_FOUND);
        }

        if (empty($admin->email)) {
            throw new BusinessException('该账号未绑定邮箱，无法重置密码', ResponseCode::BUSINESS_ERROR);
        }

        $verifyCode = VerifyCode::where('target', $admin->email)
            ->where('code', $code)
            ->where('type', VerifyCodeType::ADMIN_PWD_RESET->value)
            ->where('expire_time', '>', now())
            ->first();

        if (!$verifyCode) {
            throw new BusinessException('验证码错误或已过期', ResponseCode::VERIFY_CODE_ERROR);
        }

        if (Hash::check($newPwd, $admin->password)) {
            throw new BusinessException('新密码不能与当前密码相同', ResponseCode::BUSINESS_ERROR);
        }

        $admin->password = Hash::make($newPwd);
        $admin->save();

        $verifyCode->delete();

        $this->logBusiness('管理员重置密码成功', ['admin_id' => $admin->admin_id]);
    }

    private function formatAdmin(SysAdmin $admin): array
    {
        $labId = $admin->lab_id ?: 'software';
        return [
            'adminId'     => $admin->admin_id,
            'adminName'   => $admin->admin_name,
            'realName'    => $admin->real_name,
            'labId'       => $labId,
            'labName'     => $labId === 'ai' ? '人工智能实验室' : '软件开发实验室',
            'roles'       => ['admin'],
            'permissions' => [
                'user:list', 'user:create', 'user:delete', 'user:update',
                'course:list', 'course:create', 'course:edit', 'course:delete',
                'checkin:create', 'checkin:list', 'checkin:end',
                'homework:create', 'homework:list',
                'score:list',
                'application:list', 'application:approve',
            ],
            'phone'       => PhoneHelper::mask($admin->phone ?? ''),
            'email'       => $admin->email,
        ];
    }
}
