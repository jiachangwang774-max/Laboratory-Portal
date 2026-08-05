<?php
// 异常日志表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 异常日志表 —— 记录系统异常，支持数据库查询检索。
     */
    public function up(): void
    {
        Schema::create('sys_error_log', function (Blueprint $table) {
            $table->id()->comment('主键');
            $table->string('trace_id', 36)->nullable()->comment('链路追踪ID');
            $table->string('level', 20)->default('error')->comment('日志级别：error / warning / critical');
            $table->string('message', 500)->comment('异常描述');
            $table->text('exception_message')->nullable()->comment('原始异常消息');
            $table->string('exception_file', 255)->nullable()->comment('异常文件路径');
            $table->integer('exception_line')->nullable()->comment('异常行号');
            $table->longText('exception_trace')->nullable()->comment('异常堆栈');
            $table->string('channel', 30)->nullable()->comment('来源渠道：api / web / console');
            $table->string('url', 500)->nullable()->comment('请求URL');
            $table->string('method', 10)->nullable()->comment('请求方法');
            $table->unsignedBigInteger('user_id')->nullable()->comment('触发用户ID');
            $table->string('ip', 45)->nullable()->comment('请求IP');
            $table->json('context')->nullable()->comment('上下文数据');
            $table->dateTime('create_time')->useCurrent()->comment('发生时间');

            $table->index('trace_id');
            $table->index('level');
            $table->index('channel');
            $table->index('create_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_error_log');
    }
};
