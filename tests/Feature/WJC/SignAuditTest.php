<?php

namespace Tests\Feature\WJC;

use App\Models\SysAdmin;
use App\Models\SysUser;
use App\Models\TrainCourse;
use App\Models\TrainSign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SignAuditTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/admin/sign_audit';
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'status'     => 1,
        ]);

        $this->token = auth('admin_api')->login($admin);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    /**
     * 创建测试报名数据
     */
    private function seedSignData(): array
    {
        $user = SysUser::create([
            'username' => 'stu001',
            'password' => Hash::make('Pass@123'),
            'real_name'=> '张三',
            'phone'    => '13800138000',
            'status'   => 1,
        ]);

        $course = TrainCourse::create([
            'course_name' => '嵌入式系统实训',
            'course_desc' => '讲解单片机',
            'status'      => 1,
        ]);

        $sign = TrainSign::create([
            'user_id'      => $user->user_id,
            'course_id'    => $course->course_id,
            'sign_info'    => '本人有C语言基础',
            'audit_status' => 0,
            'sign_time'    => now(),
        ]);

        return [
            'user'   => $user,
            'course' => $course,
            'sign'   => $sign,
        ];
    }

    // ==================== 报名列表 ====================

    public function test_sign_list_success(): void
    {
        $this->seedSignData();

        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/list");

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.list.0.realName', '张三')
            ->assertJsonPath('data.list.0.courseName', '嵌入式系统实训')
            ->assertJsonPath('data.list.0.auditStatus', 0)
            ->assertJsonPath('data.list.0.statusText', '待审核');
    }

    public function test_sign_list_filter_by_audit_status(): void
    {
        $user = SysUser::create(['username' => 'u1', 'password' => Hash::make('P@ss1'), 'status' => 1]);
        $course = TrainCourse::create(['course_name' => '课程A', 'status' => 1]);

        TrainSign::create([
            'user_id'      => $user->user_id,
            'course_id'    => $course->course_id,
            'audit_status' => 0,
            'sign_time'    => now(),
        ]);
        TrainSign::create([
            'user_id'      => $user->user_id,
            'course_id'    => $course->course_id,
            'audit_status' => 1,
            'sign_time'    => now()->subDay(),
        ]);

        // 只查待审核
        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/list?auditStatus=0");

        $res->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.list.0.auditStatus', 0);
    }

    public function test_sign_list_filter_by_course(): void
    {
        $user = SysUser::create(['username' => 'u1', 'password' => Hash::make('P@ss1'), 'status' => 1]);
        $course1 = TrainCourse::create(['course_name' => '课程A', 'status' => 1]);
        $course2 = TrainCourse::create(['course_name' => '课程B', 'status' => 1]);

        TrainSign::create([
            'user_id'      => $user->user_id,
            'course_id'    => $course1->course_id,
            'audit_status' => 0,
            'sign_time'    => now(),
        ]);
        TrainSign::create([
            'user_id'      => $user->user_id,
            'course_id'    => $course2->course_id,
            'audit_status' => 0,
            'sign_time'    => now(),
        ]);

        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/list?courseId={$course1->course_id}");

        $res->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.list.0.courseName', '课程A');
    }

    public function test_sign_list_without_token(): void
    {
        $res = $this->getJson("{$this->prefix}/list");
        $res->assertStatus(401);
    }

    public function test_sign_list_invalid_audit_status(): void
    {
        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/list?auditStatus=9");

        $res->assertStatus(422);
    }

    // ==================== 报名详情 ====================

    public function test_sign_detail_success(): void
    {
        $data = $this->seedSignData();

        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/detail/{$data['sign']->sign_id}");

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.userRealName', '张三')
            ->assertJsonPath('data.courseName', '嵌入式系统实训')
            ->assertJsonPath('data.signInfo', '本人有C语言基础')
            ->assertJsonPath('data.auditStatus', 0);
    }

    public function test_sign_detail_not_found(): void
    {
        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/detail/99999");

        $res->assertStatus(404)
            ->assertJsonPath('code', 30001);
    }

    // ==================== 单条审核 ====================

    public function test_sign_single_audit_approve_success(): void
    {
        $data = $this->seedSignData();

        $res = $this->withHeaders($this->authHeader())
            ->putJson("{$this->prefix}/single_audit/{$data['sign']->sign_id}", [
                'auditStatus' => 1,
                'auditRemark' => '审核通过，请按时上课',
            ]);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('msg', '单条审核操作完成')
            ->assertJsonPath('data.signId', $data['sign']->sign_id)
            ->assertJsonPath('data.auditStatus', 1)
            ->assertJsonPath('data.statusText', '审核通过');

        $this->assertEquals(1, $data['sign']->fresh()->audit_status);
        $this->assertEquals('审核通过，请按时上课', $data['sign']->fresh()->audit_remark);
        $this->assertNotNull($data['sign']->fresh()->audit_time);
    }

    public function test_sign_single_audit_reject_success(): void
    {
        $data = $this->seedSignData();

        $res = $this->withHeaders($this->authHeader())
            ->putJson("{$this->prefix}/single_audit/{$data['sign']->sign_id}", [
                'auditStatus' => 2,
                'auditRemark' => '名额已满',
            ]);

        $res->assertOk()
            ->assertJsonPath('data.auditStatus', 2)
            ->assertJsonPath('data.statusText', '审核驳回');
    }

    public function test_sign_single_audit_duplicate(): void
    {
        $data = $this->seedSignData();

        // 第一次审核
        $this->withHeaders($this->authHeader())
            ->putJson("{$this->prefix}/single_audit/{$data['sign']->sign_id}", [
                'auditStatus' => 1,
            ]);

        // 第二次审核应被拒绝
        $res = $this->withHeaders($this->authHeader())
            ->putJson("{$this->prefix}/single_audit/{$data['sign']->sign_id}", [
                'auditStatus' => 2,
            ]);

        $res->assertStatus(429)
            ->assertJsonPath('code', 40009)
            ->assertJsonPath('msg', '该报名记录已审核，请勿重复操作');
    }

    public function test_sign_single_audit_invalid_status(): void
    {
        $data = $this->seedSignData();

        $res = $this->withHeaders($this->authHeader())
            ->putJson("{$this->prefix}/single_audit/{$data['sign']->sign_id}", [
                'auditStatus' => 0, // 不允许设回待审核
            ]);

        $res->assertStatus(422);
    }

    public function test_sign_single_audit_missing_status(): void
    {
        $data = $this->seedSignData();

        $res = $this->withHeaders($this->authHeader())
            ->putJson("{$this->prefix}/single_audit/{$data['sign']->sign_id}", [
                'auditRemark' => '忘了传状态',
            ]);

        $res->assertStatus(422);
    }

    // ==================== 批量审核 ====================

    public function test_sign_batch_audit_success(): void
    {
        $data = $this->seedSignData();

        // 再创建一个待审核记录
        $sign2 = TrainSign::create([
            'user_id'      => $data['user']->user_id,
            'course_id'    => $data['course']->course_id,
            'audit_status' => 0,
            'sign_time'    => now()->subHour(),
        ]);

        $res = $this->withHeaders($this->authHeader())
            ->postJson("{$this->prefix}/batch_audit", [
                'signIdList'  => [$data['sign']->sign_id, $sign2->sign_id],
                'auditStatus' => 1,
                'auditRemark' => '批量通过',
            ]);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('msg', '批量审核完成')
            ->assertJsonPath('data.successCount', 2)
            ->assertJsonPath('data.skippedCount', 0);
    }

    public function test_sign_batch_audit_mixed_with_already_audited(): void
    {
        $data = $this->seedSignData();

        // 第二条先单独审核
        $sign2 = TrainSign::create([
            'user_id'      => $data['user']->user_id,
            'course_id'    => $data['course']->course_id,
            'audit_status' => 0,
            'sign_time'    => now()->subHour(),
        ]);
        TrainSign::where('sign_id', $sign2->sign_id)->update([
            'audit_status' => 1,
        ]);

        // 批量审核（含1条已审核 + 1条待审核）
        $res = $this->withHeaders($this->authHeader())
            ->postJson("{$this->prefix}/batch_audit", [
                'signIdList'  => [$data['sign']->sign_id, $sign2->sign_id],
                'auditStatus' => 1,
            ]);

        $res->assertOk()
            ->assertJsonPath('data.successCount', 1)
            ->assertJsonPath('data.skippedCount', 1);
    }

    public function test_sign_batch_audit_all_already_audited(): void
    {
        $data = $this->seedSignData();
        TrainSign::where('sign_id', $data['sign']->sign_id)->update(['audit_status' => 1]);

        $res = $this->withHeaders($this->authHeader())
            ->postJson("{$this->prefix}/batch_audit", [
                'signIdList'  => [$data['sign']->sign_id],
                'auditStatus' => 1,
            ]);

        $res->assertStatus(404)
            ->assertJsonPath('code', 30001)
            ->assertJsonPath('msg', '没有可审核的报名记录');
    }

    public function test_sign_batch_audit_empty_list(): void
    {
        $res = $this->withHeaders($this->authHeader())
            ->postJson("{$this->prefix}/batch_audit", [
                'signIdList'  => [],
                'auditStatus' => 1,
            ]);

        $res->assertStatus(422);
    }

    public function test_sign_batch_audit_missing_list(): void
    {
        $res = $this->withHeaders($this->authHeader())
            ->postJson("{$this->prefix}/batch_audit", [
                'auditStatus' => 1,
            ]);

        $res->assertStatus(422);
    }
}
