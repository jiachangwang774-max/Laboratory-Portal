<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_checkins', function (Blueprint $table) {
            if (!Schema::hasColumn('course_checkins', 'title')) {
                $table->string('title', 100)->nullable()->after('checkin_code')->comment('签到标题');
            }
            if (!Schema::hasColumn('course_checkins', 'class_name')) {
                $table->string('class_name', 20)->nullable()->after('session_id')->comment('班级');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_checkins', function (Blueprint $table) {
            $table->dropColumn(['title', 'class_name']);
        });
    }
};
