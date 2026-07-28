<?php

namespace Tests\Feature\WJC;

use App\Enums\VerifyCodeType;
use App\Models\SysAdmin;
use App\Models\VerifyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/admin/auth';

    // ==================== 登录 ====================

    public function test_admin_login_success(): void
    {
        SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'real_name'  => '超级管理员',
            'phone'      => '13900139000',
            'status'     => 1,
        ]);

        $res = $this->postJson("{$this->prefix}/login", [
            'adminName' => 'superadmin',
            'password'  => 'Admin@123',
        ]);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('msg', '登录成功')
            ->assertJsonStructure([
                'code', 'msg', 'data' => ['accessToken', 'adminInfo'],
                'success', 'trace_id',
            ])
            ->assertJsonPath('data.adminInfo.adminName', 'superadmin');
    }

    public function test_admin_login_wrong_password(): void
    {
        SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'status'     => 1,
        ]);

        $res = $this->postJson("{$this->prefix}/login", [
            'adminName' => 'superadmin',
            'password'  => 'WrongPass1!',
        ]);

        $res->assertStatus(401)
            ->assertJsonPath('code', 20008);
    }

    public function test_admin_login_disabled_account(): void
    {
        SysAdmin::create([
            'admin_name' => 'disabled_admin',
            'password'   => Hash::make('Admin@123'),
            'status'     => 0,
        ]);

        $res = $this->postJson("{$this->prefix}/login", [
            'adminName' => 'disabled_admin',
            'password'  => 'Admin@123',
        ]);

        $res->assertStatus(403)
            ->assertJsonPath('code', 20006);
    }

    public function test_admin_login_missing_params(): void
    {
        $res = $this->postJson("{$this->prefix}/login", []);
        $res->assertStatus(422)
            ->assertJsonPath('code', 10001);
    }

    public function test_admin_login_empty_admin_name(): void
    {
        $res = $this->postJson("{$this->prefix}/login", [
            'adminName' => '',
            'password'  => 'Admin@123',
        ]);
        $res->assertStatus(422);
    }

    // ==================== 登出 ====================

    public function test_admin_logout_success(): void
    {
        $admin = SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'status'     => 1,
        ]);
        $token = auth('admin_api')->login($admin);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("{$this->prefix}/logout");

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('msg', '后台登出成功');
    }

    public function test_admin_logout_without_token(): void
    {
        $res = $this->postJson("{$this->prefix}/logout");
        $res->assertStatus(401);
    }

    public function test_admin_logout_invalid_token(): void
    {
        $res = $this->withHeader('Authorization', 'Bearer invalid_token_xyz')
            ->postJson("{$this->prefix}/logout");
        $res->assertStatus(401);
    }

    // ==================== 获取信息 ====================

    public function test_admin_info_success(): void
    {
        $admin = SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'real_name'  => '超级管理员',
            'phone'      => '13900139000',
            'email'      => 'admin@lab.com',
            'status'     => 1,
        ]);
        $token = auth('admin_api')->login($admin);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("{$this->prefix}/info");

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.adminName', 'superadmin')
            ->assertJsonPath('data.realName', '超级管理员')
            ->assertJsonPath('data.email', 'admin@lab.com');
    }

    public function test_admin_info_without_token(): void
    {
        $res = $this->getJson("{$this->prefix}/info");
        $res->assertStatus(401);
    }

    // ==================== 修改密码 ====================

    public function test_admin_update_pwd_success(): void
    {
        $admin = SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'status'     => 1,
        ]);
        $token = auth('admin_api')->login($admin);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("{$this->prefix}/update_pwd", [
                'oldPwd' => 'Admin@123',
                'newPwd' => 'NewPass@456',
            ]);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('msg', '管理员密码修改完成');

        // 验证新密码可以登录
        $loginRes = $this->postJson("{$this->prefix}/login", [
            'adminName' => 'superadmin',
            'password'  => 'NewPass@456',
        ]);
        $loginRes->assertOk();
    }

    public function test_admin_update_pwd_wrong_old_password(): void
    {
        $admin = SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'status'     => 1,
        ]);
        $token = auth('admin_api')->login($admin);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("{$this->prefix}/update_pwd", [
                'oldPwd' => 'WrongOldPwd1!',
                'newPwd' => 'NewPass@456',
            ]);

        $res->assertStatus(401)
            ->assertJsonPath('code', 20008);
    }

    public function test_admin_update_pwd_same_as_current(): void
    {
        $admin = SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'status'     => 1,
        ]);
        $token = auth('admin_api')->login($admin);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("{$this->prefix}/update_pwd", [
                'oldPwd' => 'Admin@123',
                'newPwd' => 'Admin@123', // 新旧相同
            ]);

        $res->assertStatus(400)
            ->assertJsonPath('code', 40001);
    }

    public function test_admin_update_pwd_weak_new_password(): void
    {
        $admin = SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'status'     => 1,
        ]);
        $token = auth('admin_api')->login($admin);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("{$this->prefix}/update_pwd", [
                'oldPwd' => 'Admin@123',
                'newPwd' => '123456', // 纯数字弱密码
            ]);

        $res->assertStatus(422);
    }

    // ==================== 发送验证码 ====================

    public function test_admin_send_code_success(): void
    {
        SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'phone'      => '13900139000',
            'status'     => 1,
        ]);

        $res = $this->postJson("{$this->prefix}/send_code", [
            'phone' => '13900139000',
        ]);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('msg', '验证码已下发');

        $this->assertDatabaseHas('verify_code', [
            'target' => '13900139000',
            'type'   => VerifyCodeType::ADMIN_PWD_RESET->value,
        ]);
    }

    public function test_admin_send_code_phone_not_bound(): void
    {
        $res = $this->postJson("{$this->prefix}/send_code", [
            'phone' => '13800138000',
        ]);

        $res->assertStatus(404)
            ->assertJsonPath('code', 30001)
            ->assertJsonPath('msg', '该手机号未绑定管理员账号');
    }

    public function test_admin_send_code_invalid_phone(): void
    {
        $res = $this->postJson("{$this->prefix}/send_code", [
            'phone' => '12345',
        ]);

        $res->assertStatus(422);
    }

    // ==================== 重置密码 ====================

    public function test_admin_reset_pwd_success(): void
    {
        SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'phone'      => '13900139000',
            'status'     => 1,
        ]);

        // 先发验证码
        $this->postJson("{$this->prefix}/send_code", ['phone' => '13900139000']);

        $verifyCode = VerifyCode::where('target', '13900139000')->first();

        $res = $this->postJson("{$this->prefix}/reset_pwd", [
            'phone'  => '13900139000',
            'code'   => $verifyCode->code,
            'newPwd' => 'NewPass@789',
        ]);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('msg', '管理员密码重置成功');

        // 验证码应被删除
        $this->assertDatabaseMissing('verify_code', ['id' => $verifyCode->id]);

        // 新密码可登录
        $loginRes = $this->postJson("{$this->prefix}/login", [
            'adminName' => 'superadmin',
            'password'  => 'NewPass@789',
        ]);
        $loginRes->assertOk();
    }

    public function test_admin_reset_pwd_wrong_code(): void
    {
        SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'phone'      => '13900139000',
            'status'     => 1,
        ]);

        $res = $this->postJson("{$this->prefix}/reset_pwd", [
            'phone'  => '13900139000',
            'code'   => '000000',
            'newPwd' => 'NewPass@789',
        ]);

        $res->assertStatus(400)
            ->assertJsonPath('code', 40002);
    }

    public function test_admin_reset_pwd_expired_code(): void
    {
        SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'phone'      => '13900139000',
            'status'     => 1,
        ]);

        VerifyCode::create([
            'target'      => '13900139000',
            'code'        => '123456',
            'type'        => VerifyCodeType::ADMIN_PWD_RESET->value,
            'expire_time' => now()->subMinute(), // 已过期
            'create_time' => now(),
        ]);

        $res = $this->postJson("{$this->prefix}/reset_pwd", [
            'phone'  => '13900139000',
            'code'   => '123456',
            'newPwd' => 'NewPass@789',
        ]);

        $res->assertStatus(400)
            ->assertJsonPath('code', 40002);
    }

    public function test_admin_reset_pwd_same_as_current(): void
    {
        SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'phone'      => '13900139000',
            'status'     => 1,
        ]);

        $this->postJson("{$this->prefix}/send_code", ['phone' => '13900139000']);
        $verifyCode = VerifyCode::where('target', '13900139000')->first();

        $res = $this->postJson("{$this->prefix}/reset_pwd", [
            'phone'  => '13900139000',
            'code'   => $verifyCode->code,
            'newPwd' => 'Admin@123', // 与当前密码相同
        ]);

        $res->assertStatus(400)
            ->assertJsonPath('code', 40001);
    }

    // ==================== 统一响应格式 ====================

    public function test_any_auth_response_has_trace_id(): void
    {
        SysAdmin::create([
            'admin_name' => 'admin',
            'password'   => Hash::make('Admin@123'),
            'status'     => 1,
        ]);

        $res = $this->postJson("{$this->prefix}/login", [
            'adminName' => 'admin',
            'password'  => 'Admin@123',
        ]);

        $res->assertJsonPath('success', true);
        $this->assertNotNull($res->json('trace_id'));
    }
}
