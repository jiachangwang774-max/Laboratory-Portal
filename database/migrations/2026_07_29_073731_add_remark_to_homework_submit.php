<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('homework_submit', 'remark')) {
            Schema::table('homework_submit', function (Blueprint $table) {
                $table->string('remark', 200)->nullable()->comment('教师评语')->after('score');
            });
        }
    }

    public function down(): void
    {
        Schema::table('homework_submit', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
};
