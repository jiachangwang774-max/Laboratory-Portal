<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sys_user', 'lab_id')) {
            Schema::table('sys_user', function (Blueprint $table) { $table->string('lab_id', 20)->default('software'); });
        }
        if (!Schema::hasColumn('checkin_records', 'lab_id')) {
            Schema::table('checkin_records', function (Blueprint $table) { $table->string('lab_id', 20)->default('software'); });
        }
        if (!Schema::hasColumn('homework_submit', 'lab_id')) {
            Schema::table('homework_submit', function (Blueprint $table) { $table->string('lab_id', 20)->default('software'); });
        }
        if (!Schema::hasColumn('sign_application', 'lab_id')) {
            Schema::table('sign_application', function (Blueprint $table) { $table->string('lab_id', 20)->default('software'); });
            \Illuminate\Support\Facades\DB::statement("UPDATE sign_application SET lab_id = CASE WHEN department = 2 THEN 'ai' ELSE 'software' END");
        }
    }

    public function down(): void
    {
        Schema::table('sys_user', function (Blueprint $table) { $table->dropColumn('lab_id'); });
        Schema::table('checkin_records', function (Blueprint $table) { $table->dropColumn('lab_id'); });
        Schema::table('homework_submit', function (Blueprint $table) { $table->dropColumn('lab_id'); });
        Schema::table('sign_application', function (Blueprint $table) { $table->dropColumn('lab_id'); });
    }
};
