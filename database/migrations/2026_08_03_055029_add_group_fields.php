<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('train_course', function (Blueprint $table) {
            if (!Schema::hasColumn('train_course', 'group_count')) {
                $table->integer('group_count')->default(1)->comment('分班数量')->after('max_sign');
            }
        });
    }

    public function down(): void
    {
        Schema::table('train_course', function (Blueprint $table) {
            $table->dropColumn('group_count');
        });
    }
};
