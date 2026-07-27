<?php

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
            $table->tinyInteger('audit_status')->default(0)->comment('0待审核 1通过 2驳回');
            $table->unsignedBigInteger('audit_admin')->nullable()->comment('审核管理员ID');
            $table->string('audit_remark', 200)->nullable()->comment('审核备注');
            $table->dateTime('sign_time')->useCurrent()->comment('报名时间');
            $table->dateTime('audit_time')->nullable()->comment('审核时间');
            $table->unique(['user_id', 'course_id'], 'uk_user_course');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_sign');
    }
};
