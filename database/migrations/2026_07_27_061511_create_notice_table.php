<?php
// 公告表
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice', function (Blueprint $table) {
            $table->id('notice_id')->comment('公告ID');
            $table->string('title', 100)->comment('公告标题');
            $table->longText('content')->comment('公告正文');
            $table->string('cover', 255)->nullable()->comment('公告封面');
            $table->tinyInteger('is_top')->default(0)->comment('0普通 1置顶');
            $table->tinyInteger('status')->default(1)->comment('0下架 1正常展示');
            $table->unsignedBigInteger('create_admin')->comment('发布管理员ID');
            $table->dateTime('create_time')->useCurrent()->comment('发布时间');
            $table->dateTime('update_time')->nullable()->useCurrentOnUpdate()->comment('修改时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice');
    }
};
