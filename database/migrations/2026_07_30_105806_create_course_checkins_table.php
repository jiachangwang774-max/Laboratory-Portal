<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_checkins', function (Blueprint $table) {
            $table->bigIncrements('checkin_id');
            $table->unsignedBigInteger('course_id')->comment('课程ID');
            $table->unsignedBigInteger('session_id')->nullable()->comment('课程安排ID');
            $table->string('checkin_code', 6)->comment('6位签到码');
            $table->tinyInteger('status')->default(1)->comment('1进行中 0已结束');
            $table->unsignedBigInteger('create_admin')->nullable();
            $table->dateTime('create_time')->useCurrent();
            $table->dateTime('end_time')->nullable();
            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_checkins');
    }
};
