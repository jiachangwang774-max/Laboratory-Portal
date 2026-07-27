<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_admin', function (Blueprint $table) {
            $table->id('admin_id')->comment('管理员ID');
            $table->string('admin_name', 50)->unique()->comment('管理员账号');
            $table->string('password', 100)->comment('加密密码');
            $table->string('real_name', 20)->nullable()->comment('管理员姓名');
            $table->string('phone', 11)->nullable()->comment('手机号');
            $table->string('email', 50)->nullable()->comment('邮箱');
            $table->tinyInteger('status')->default(1)->comment('账号状态 0禁用 1正常');
            $table->dateTime('create_time')->useCurrent()->comment('创建时间');
            $table->dateTime('update_time')->nullable()->useCurrentOnUpdate()->comment('更新时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_admin');
    }
};
