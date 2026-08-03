<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sign_application', function (Blueprint $table) {
            if (!Schema::hasColumn('sign_application', 'group_name')) {
                $table->string('group_name', 20)->nullable()->comment('分班')->after('audit_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sign_application', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });
    }
};
