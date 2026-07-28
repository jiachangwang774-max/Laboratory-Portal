<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_user', function (Blueprint $table) {
            $table->id('user_id')->comment('用户主键');
            $table->string('username', 50)->unique()->comment('登录账号');
            $table->string('password', 100)->comment('加密密码');
            $table->string('real_name', 20)->nullable()->comment('真实姓名');
            $table->string('phone', 11)->nullable()->unique()->comment('手机号码');
            $table->string('email', 50)->nullable()->comment('邮箱');
            $table->string('grade', 50)->nullable()->comment('年级');
            $table->string('major', 100)->nullable()->comment('专业');
            $table->string('college', 100)->nullable()->comment('学院');
            $table->string('student_id', 50)->nullable()->comment('学号');
            $table->string('avatar', 255)->nullable()->comment('头像url');
            $table->tinyInteger('status')->default(1)->comment('状态 0禁用 1正常');
            $table->dateTime('create_time')->useCurrent()->comment('创建时间');
            $table->dateTime('update_time')->nullable()->useCurrentOnUpdate()->comment('更新时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_user');
    }
};
