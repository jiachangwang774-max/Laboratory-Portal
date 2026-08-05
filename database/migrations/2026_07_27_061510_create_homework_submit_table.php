<?php
// 作业提交表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_submit', function (Blueprint $table) {
            $table->id('submit_id')->comment('提交记录ID');
            $table->unsignedBigInteger('user_id')->comment('提交学员ID');
            $table->unsignedBigInteger('homework_id')->comment('对应作业ID');
            $table->text('submit_content')->nullable()->comment('文字作答内容');
            $table->string('submit_file', 500)->nullable()->comment('附件文件地址');
            $table->dateTime('submit_time')->useCurrent()->comment('提交时间');
            $table->integer('score')->nullable()->comment('批阅分数');
            $table->string('remark', 200)->nullable()->comment('教师评语');
            $table->unique(['user_id', 'homework_id'], 'uk_user_homework');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_submit');
    }
};
