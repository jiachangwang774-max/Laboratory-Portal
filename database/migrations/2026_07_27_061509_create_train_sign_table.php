<?php
// 报名表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_sign', function (Blueprint $table) {
            $table->id('sign_id')->comment('报名主键');
            $table->unsignedBigInteger('user_id')->comment('报名学员ID');
            $table->unsignedBigInteger('course_id')->comment('对应课程ID');
            $table->text('sign_info')->nullable()->comment('报名附加信息');
            $table->tinyInteger('status')->default(1)->comment('1正常 0取消');
            $table->string('group_name', 20)->nullable()->comment('分班名称');
            $table->dateTime('sign_time')->useCurrent()->comment('报名时间');
            $table->unique(['user_id', 'course_id'], 'uk_user_course');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_sign');
    }
};
