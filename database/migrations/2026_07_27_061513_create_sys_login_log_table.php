<?php
// 登录日志表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 登录日志表 —— 记录所有用户和管理员的登录行为。
     */
    public function up(): void
    {
        Schema::create('sys_login_log', function (Blueprint $table) {
            $table->id()->comment('主键');
            $table->string('trace_id', 36)->nullable()->comment('链路追踪ID');
            $table->string('login_type', 20)->comment('登录类型：user / admin');
            $table->unsignedBigInteger('user_id')->nullable()->comment('登录用户ID');
            $table->string('username', 50)->comment('登录账号');
            $table->tinyInteger('status')->default(1)->comment('1成功 0失败');
            $table->string('fail_reason', 100)->nullable()->comment('失败原因（密码错误/账号禁用等）');
            $table->string('ip', 45)->nullable()->comment('登录IP');
            $table->string('user_agent', 500)->nullable()->comment('浏览器UA');
            $table->dateTime('login_time')->useCurrent()->comment('登录时间');

            $table->index('login_type');
            $table->index('user_id');
            $table->index('username');
            $table->index('login_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_login_log');
    }
};
