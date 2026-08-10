<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('train_homework', function (Blueprint $table) {
            // create_admin 列在 model fillable 中已引用，但原 migration 遗漏
            if (!Schema::hasColumn('train_homework', 'create_admin')) {
                $table->unsignedBigInteger('create_admin')->nullable()->after('lab_id')->comment('创建管理员ID');
            }
            if (!Schema::hasColumn('train_homework', 'questions')) {
                $table->json('questions')->nullable()->after('homework_content')->comment('结构化题目列表');
            }
        });
    }

    public function down(): void
    {
        Schema::table('train_homework', function (Blueprint $table) {
            if (Schema::hasColumn('train_homework', 'questions')) {
                $table->dropColumn('questions');
            }
            // create_admin 不回滚，避免误删已有数据
        });
    }
};
