<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('train_course', function (Blueprint $table) {
            if (!Schema::hasColumn('train_course', 'group_name')) {
                $table->string('group_name', 20)->nullable()->comment('指定班级，空为全部')->after('group_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('train_course', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });
    }
};
