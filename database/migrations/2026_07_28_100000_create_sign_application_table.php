<?php
// 报名申请表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sign_application', function (Blueprint $table) {
            $table->id()->comment('报名主键');
            $table->unsignedBigInteger('user_id')->nullable()->comment('用户ID（可选，未登录也可报名）');
            $table->string('name', 50)->nullable()->comment('姓名');
            $table->string('student_id', 50)->comment('学号');
            $table->tinyInteger('department')->nullable()->comment('报名部门 1软件开发实验室 2人工智能实验室');
            $table->string('college', 100)->nullable()->comment('学院');
            $table->string('major', 100)->nullable()->comment('专业');
            $table->string('class_name', 100)->nullable()->comment('班级');
            $table->string('phone', 20)->nullable()->comment('手机号');
            $table->text('self_introduction')->nullable()->comment('自我介绍');
            $table->tinyInteger('status')->default(0)->comment('0草稿 1已提交');
            $table->tinyInteger('audit_status')->default(0)->comment('0待审核 1通过 2驳回');
            $table->string('group_name', 20)->nullable()->comment('分班');
            $table->unsignedBigInteger('audit_admin')->nullable()->comment('审核管理员ID');
            $table->string('audit_remark', 200)->nullable()->comment('审核备注');
            $table->dateTime('audit_time')->nullable()->comment('审核时间');
            $table->dateTime('submit_time')->nullable()->comment('提交时间');
            $table->dateTime('create_time')->useCurrent()->comment('创建时间');
            $table->dateTime('update_time')->useCurrent()->useCurrentOnUpdate()->comment('更新时间');

            $table->unique('student_id', 'uk_student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sign_application');
    }
};
