<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('train_course', function (Blueprint $table) {
            $table->string('instructor', 50)->nullable()->after('course_desc')->comment('讲师');
            $table->string('course_date', 100)->nullable()->after('instructor')->comment('课程日期');
            $table->string('location', 200)->nullable()->after('course_date')->comment('上课地点');
        });
    }

    public function down(): void
    {
        Schema::table('train_course', function (Blueprint $table) {
            $table->dropColumn(['instructor', 'course_date', 'location']);
        });
    }
};
