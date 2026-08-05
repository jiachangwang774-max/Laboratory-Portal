<?php
// 课程表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_course', function (Blueprint $table) {
            $table->id('course_id')->comment('课程ID');
            $table->string('course_name', 100)->comment('课程名称');
            $table->text('course_desc')->nullable()->comment('课程介绍');
            $table->string('instructor', 50)->nullable()->comment('讲师');
            $table->string('course_date', 100)->nullable()->comment('课程日期');
            $table->string('location', 200)->nullable()->comment('上课地点');
            $table->string('cover_img', 255)->nullable()->comment('课程封面图');
            $table->dateTime('start_time')->nullable()->comment('开课时间');
            $table->dateTime('end_time')->nullable()->comment('结课时间');
            $table->integer('max_sign')->default(100)->comment('最大报名人数');
            $table->integer('group_count')->default(1)->comment('分班数量');
            $table->string('group_name', 20)->nullable()->comment('指定班级，空为全部');
            $table->tinyInteger('status')->default(1)->comment('0下架 1正常展示');
            $table->string('lab_id', 20)->default('software');
            $table->unsignedBigInteger('create_admin')->nullable()->comment('创建管理员ID');
            $table->dateTime('create_time')->useCurrent()->comment('创建时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_course');
    }
};
