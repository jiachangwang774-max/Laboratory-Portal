<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_user', function (Blueprint $table) {
            $table->string('grade', 50)->nullable()->after('email')->comment('年级');
            $table->string('major', 100)->nullable()->after('grade')->comment('专业');
        });
    }

    public function down(): void
    {
        Schema::table('sys_user', function (Blueprint $table) {
            $table->dropColumn(['grade', 'major']);
        });
    }
};
