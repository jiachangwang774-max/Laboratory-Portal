<?php

namespace Tests\Feature\WJC;

use App\Models\Notice;
use App\Models\SysAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NoticeTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/admin/notice';
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = SysAdmin::create([
            'admin_name' => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'real_name'  => '超级管理员',
            'status'     => 1,
        ]);

        $this->token = auth('admin_api')->login($admin);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ==================== 创建公告 ====================

    public function test_notice_create_success(): void
    {
        $res = $this->withHeaders($this->authHeader())
            ->postJson("{$this->prefix}/create", [
                'title'   => '实验室暑期培训通知',
                'content' => '详细培训安排...',
                'isTop'   => 1,
            ]);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('msg', '公告发布成功')
            ->assertJsonPath('data.title', '实验室暑期培训通知');

        $this->assertDatabaseHas('notice', [
            'title'        => '实验室暑期培训通知',
            'status'       => 1,
            'is_top'       => 1,
            'create_admin' => 1,
        ]);
    }

    public function test_notice_create_with_cover(): void
    {
        $res = $this->withHeaders($this->authHeader())
            ->postJson("{$this->prefix}/create", [
                'title'   => '带封面公告',
                'content' => '正文',
                'cover'   => 'https://example.com/cover.jpg',
                'isTop'   => 0,
            ]);

        $res->assertOk();
        $this->assertEquals(1, Notice::count());
    }

    public function test_notice_create_missing_title(): void
    {
        $res = $this->withHeaders($this->authHeader())
            ->postJson("{$this->prefix}/create", [
                'content' => '只有内容没有标题',
            ]);

        $res->assertStatus(422);
    }

    public function test_notice_create_missing_content(): void
    {
        $res = $this->withHeaders($this->authHeader())
            ->postJson("{$this->prefix}/create", [
                'title' => '只有标题没有内容',
            ]);

        $res->assertStatus(422);
    }

    public function test_notice_create_invalid_is_top(): void
    {
        $res = $this->withHeaders($this->authHeader())
            ->postJson("{$this->prefix}/create", [
                'title'   => '测试',
                'content' => '正文',
                'isTop'   => 99, // 只能是 0 或 1
            ]);

        $res->assertStatus(422);
    }

    public function test_notice_create_without_token(): void
    {
        $res = $this->postJson("{$this->prefix}/create", [
            'title'   => '未登录创建',
            'content' => '正文',
        ]);

        $res->assertStatus(401);
    }

    // ==================== 删除公告 ====================

    public function test_notice_delete_success(): void
    {
        $notice = Notice::create([
            'title'        => '待删除公告',
            'content'      => '内容',
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->withHeaders($this->authHeader())
            ->deleteJson("{$this->prefix}/delete/{$notice->notice_id}");

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('msg', '公告删除成功');

        $this->assertDatabaseMissing('notice', ['notice_id' => $notice->notice_id]);
    }

    public function test_notice_delete_not_found(): void
    {
        $res = $this->withHeaders($this->authHeader())
            ->deleteJson("{$this->prefix}/delete/99999");

        $res->assertStatus(404)
            ->assertJsonPath('code', 30001);
    }

    // ==================== 更新公告 ====================

    public function test_notice_update_success(): void
    {
        $notice = Notice::create([
            'title'        => '原始标题',
            'content'      => '原始内容',
            'is_top'       => 0,
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->withHeaders($this->authHeader())
            ->putJson("{$this->prefix}/update/{$notice->notice_id}", [
                'title'  => '修改后的标题',
                'isTop'  => 1,
                'status' => 0,
            ]);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('msg', '公告编辑成功')
            ->assertJsonPath('data.title', '修改后的标题');

        $this->assertEquals('修改后的标题', $notice->fresh()->title);
        $this->assertEquals(1, $notice->fresh()->is_top);
        $this->assertEquals(0, $notice->fresh()->status);
    }

    public function test_notice_update_partial(): void
    {
        $notice = Notice::create([
            'title'        => '原始标题',
            'content'      => '原始内容',
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->withHeaders($this->authHeader())
            ->putJson("{$this->prefix}/update/{$notice->notice_id}", [
                'title' => '只改标题',
            ]);

        $res->assertOk();
        $this->assertEquals('只改标题', $notice->fresh()->title);
        $this->assertEquals('原始内容', $notice->fresh()->content);
    }

    public function test_notice_update_invalid_status(): void
    {
        $notice = Notice::create([
            'title'        => '公告',
            'content'      => '内容',
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->withHeaders($this->authHeader())
            ->putJson("{$this->prefix}/update/{$notice->notice_id}", [
                'status' => 9, // 只能 0 或 1
            ]);

        $res->assertStatus(422);
    }

    // ==================== 后台公告列表 ====================

    public function test_notice_list_success(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            Notice::create([
                'title'        => "公告 {$i}",
                'content'      => "内容 {$i}",
                'status'       => 1,
                'create_admin' => 1,
                'create_time'  => now(),
            ]);
        }

        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/list?page=1&size=10");

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.total', 15)
            ->assertJsonCount(10, 'data.list');
    }

    public function test_notice_list_with_title_search(): void
    {
        Notice::create([
            'title'        => '暑期培训通知',
            'content'      => '内容A',
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);
        Notice::create([
            'title'        => '期末考试安排',
            'content'      => '内容B',
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/list?title=培训");

        $res->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.list.0.title', '暑期培训通知');
    }

    public function test_notice_list_includes_disabled(): void
    {
        Notice::create([
            'title'        => '上架公告',
            'content'      => 'A',
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);
        Notice::create([
            'title'        => '下架公告',
            'content'      => 'B',
            'status'       => 0,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/list");

        // 后台列表应包含已下架公告
        $res->assertJsonPath('data.total', 2);
    }

    public function test_notice_list_sort_by_top_first(): void
    {
        Notice::create([
            'title'        => '普通公告',
            'content'      => 'A',
            'is_top'       => 0,
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);
        Notice::create([
            'title'        => '置顶公告',
            'content'      => 'B',
            'is_top'       => 1,
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/list");

        // 置顶公告应排在前面
        $this->assertEquals('置顶公告', $res->json('data.list.0.title'));
    }

    // ==================== 后台公告详情 ====================

    public function test_notice_detail_success(): void
    {
        $notice = Notice::create([
            'title'        => '详细公告',
            'content'      => '完整正文内容',
            'cover'        => 'https://img.example.com/cover.jpg',
            'is_top'       => 1,
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/detail/{$notice->notice_id}");

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.title', '详细公告')
            ->assertJsonPath('data.content', '完整正文内容')
            ->assertJsonPath('data.cover', 'https://img.example.com/cover.jpg');
    }

    public function test_notice_detail_not_found(): void
    {
        $res = $this->withHeaders($this->authHeader())
            ->getJson("{$this->prefix}/detail/99999");

        $res->assertStatus(404)
            ->assertJsonPath('code', 30001);
    }

    // ==================== 前台公告列表（公开接口） ====================

    public function test_notice_front_list_only_enabled(): void
    {
        Notice::create([
            'title'        => '上架公告',
            'content'      => 'A',
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);
        Notice::create([
            'title'        => '下架公告',
            'content'      => 'B',
            'status'       => 0,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->getJson("{$this->prefix}/front/list");

        $res->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.list.0.title', '上架公告');
    }

    public function test_notice_front_list_no_token_required(): void
    {
        Notice::create([
            'title'        => '公开公告',
            'content'      => '正文',
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        // 不携带 token 也能访问
        $res = $this->getJson("{$this->prefix}/front/list");

        $res->assertOk()
            ->assertJsonPath('code', 0);
    }

    public function test_notice_front_list_not_include_content(): void
    {
        Notice::create([
            'title'        => '公告',
            'content'      => '不该暴露的正文',
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->getJson("{$this->prefix}/front/list");

        $res->assertOk()
            ->assertJsonMissingPath('data.list.0.content');
    }

    // ==================== 前台公告详情（公开接口） ====================

    public function test_notice_front_detail_success(): void
    {
        $notice = Notice::create([
            'title'        => '公开公告',
            'content'      => '公开正文',
            'cover'        => 'https://example.com/cover.jpg',
            'status'       => 1,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->getJson("{$this->prefix}/front/detail/{$notice->notice_id}");

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.title', '公开公告')
            ->assertJsonPath('data.content', '公开正文');
    }

    public function test_notice_front_detail_disabled_not_accessible(): void
    {
        $notice = Notice::create([
            'title'        => '已下架公告',
            'content'      => '不应展示',
            'status'       => 0,
            'create_admin' => 1,
            'create_time'  => now(),
        ]);

        $res = $this->getJson("{$this->prefix}/front/detail/{$notice->notice_id}");

        $res->assertStatus(404)
            ->assertJsonPath('code', 30001)
            ->assertJsonPath('msg', '公告不存在或已下架');
    }

    public function test_notice_front_detail_not_found(): void
    {
        $res = $this->getJson("{$this->prefix}/front/detail/99999");

        $res->assertStatus(404)
            ->assertJsonPath('code', 30001);
    }
}
