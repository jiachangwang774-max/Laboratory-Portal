<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_admin', function (Blueprint $table) {
            if (!Schema::hasColumn('sys_admin', 'department')) {
                $table->tinyInteger('department')->default(1)->comment('1软件开发 2人工智能');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sys_admin', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
};
