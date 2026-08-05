<?php
// 签到记录表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkin_records', function (Blueprint $table) {
            $table->bigIncrements('record_id');
            $table->unsignedBigInteger('checkin_id');
            $table->unsignedBigInteger('user_id')->comment('学员ID');
            $table->string('checkin_method', 10)->default('code')->comment('code扫码签到 manual手动');
            $table->dateTime('checkin_time')->nullable();
            $table->unique(['checkin_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkin_records');
    }
};
