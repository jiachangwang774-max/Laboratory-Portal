<?php
// 课程安排表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sessions', function (Blueprint $table) {
            $table->bigIncrements('session_id');
            $table->unsignedBigInteger('course_id')->comment('所属课程ID');
            $table->string('title', 100)->comment('安排标题');
            $table->text('content')->nullable()->comment('内容描述');
            $table->dateTime('session_date')->nullable()->comment('上课时间');
            $table->dateTime('end_time')->nullable()->comment('下课时间');
            $table->string('location', 200)->nullable()->comment('地点');
            $table->string('instructor', 50)->nullable()->comment('讲师');
            $table->tinyInteger('status')->default(1)->comment('1正常 0取消');
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('create_admin')->nullable();
            $table->dateTime('create_time')->useCurrent();
            $table->dateTime('update_time')->nullable();
            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sessions');
    }
};
