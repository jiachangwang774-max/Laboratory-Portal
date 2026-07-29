<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('train_training', function (Blueprint $table) {
            $table->id('training_id')->comment('培训ID');
            $table->string('training_time', 200)->nullable()->comment('培训时间（灵活格式：周数/年月日等）');
            $table->text('training_content')->nullable()->comment('培训内容');
            $table->tinyInteger('status')->default(1)->comment('0下架 1正常');
            $table->unsignedBigInteger('create_admin')->nullable()->comment('创建管理员ID');
            $table->dateTime('create_time')->useCurrent()->comment('创建时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('train_training');
    }
};
