<?php
// 验证码表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verify_code', function (Blueprint $table) {
            $table->id()->comment('主键');
            $table->string('target', 50)->comment('手机号/邮箱');
            $table->string('code', 10)->comment('验证码');
            $table->tinyInteger('type')->comment('1用户重置密码 2管理员重置密码');
            $table->dateTime('expire_time')->comment('失效时间');
            $table->dateTime('create_time')->useCurrent()->comment('生成时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verify_code');
    }
};
