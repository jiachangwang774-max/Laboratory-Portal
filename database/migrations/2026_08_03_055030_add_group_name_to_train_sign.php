<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('train_sign', function (Blueprint $table) {
            if (!Schema::hasColumn('train_sign', 'group_name')) {
                $table->string('group_name', 20)->nullable()->comment('分班名称')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('train_sign', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });
    }
};
