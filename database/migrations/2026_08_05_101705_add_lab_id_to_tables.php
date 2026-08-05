<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // sys_admin
        if (!Schema::hasColumn('sys_admin', 'lab_id')) {
            Schema::table('sys_admin', function (Blueprint $table) {
                $table->string('lab_id', 20)->default('software')->comment('software/ai');
            });
            DB::statement("UPDATE sys_admin SET lab_id = CASE WHEN department = 2 THEN 'ai' ELSE 'software' END");
        }

        // train_course
        if (!Schema::hasColumn('train_course', 'lab_id')) {
            Schema::table('train_course', function (Blueprint $table) {
                $table->string('lab_id', 20)->default('software');
            });
        }

        // train_homework
        if (!Schema::hasColumn('train_homework', 'lab_id')) {
            Schema::table('train_homework', function (Blueprint $table) {
                $table->string('lab_id', 20)->default('software');
            });
        }

        // course_checkins
        if (!Schema::hasColumn('course_checkins', 'lab_id')) {
            Schema::table('course_checkins', function (Blueprint $table) {
                $table->string('lab_id', 20)->default('software');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sys_admin', function (Blueprint $table) { $table->dropColumn('lab_id'); });
        Schema::table('train_course', function (Blueprint $table) { $table->dropColumn('lab_id'); });
        Schema::table('train_homework', function (Blueprint $table) { $table->dropColumn('lab_id'); });
        Schema::table('course_checkins', function (Blueprint $table) { $table->dropColumn('lab_id'); });
    }
};
