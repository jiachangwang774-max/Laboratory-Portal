<?php
// 作业表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_homework', function (Blueprint $table) {
            $table->id('homework_id')->comment('作业ID');
            $table->unsignedBigInteger('course_id')->comment('所属课程ID');
            $table->string('homework_title', 100)->comment('作业标题');
            $table->text('homework_content')->nullable()->comment('作业要求');
            $table->string('group_name', 20)->nullable()->comment('指定班级，空为全部');
            $table->string('lab_id', 20)->default('software');
            $table->dateTime('deadline')->nullable()->comment('截止提交时间');
            $table->dateTime('create_time')->useCurrent()->comment('创建时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_homework');
    }
};
