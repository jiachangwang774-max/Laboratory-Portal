<?php
// 操作日志表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 操作日志表 —— 记录管理员/用户的关键操作（审核报名、创建课程、发布公告等）。
     */
    public function up(): void
    {
        Schema::create('sys_operation_log', function (Blueprint $table) {
            $table->id()->comment('主键');
            $table->string('trace_id', 36)->nullable()->comment('链路追踪ID');
            $table->string('module', 50)->comment('操作模块，如 sign_audit / train_course / notice');
            $table->string('action', 100)->comment('操作描述，如 审核通过报名 / 创建培训课程');
            $table->string('operator_type', 20)->comment('操作者类型：admin / user');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作者ID（admin_id 或 user_id）');
            $table->string('operator_name', 50)->nullable()->comment('操作者名称');
            $table->string('target_type', 50)->nullable()->comment('操作对象类型，如 train_sign / notice');
            $table->unsignedBigInteger('target_id')->nullable()->comment('操作对象ID');
            $table->json('before_data')->nullable()->comment('变更前数据（审计追溯）');
            $table->json('after_data')->nullable()->comment('变更后数据');
            $table->string('ip', 45)->nullable()->comment('操作IP');
            $table->string('user_agent', 500)->nullable()->comment('浏览器UA');
            $table->dateTime('create_time')->useCurrent()->comment('操作时间');

            $table->index('trace_id');
            $table->index('operator_type');
            $table->index('operator_id');
            $table->index('module');
            $table->index('create_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_operation_log');
    }
};
