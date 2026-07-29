<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('train_sign', function (Blueprint $table) {
            $table->dropColumn(['audit_status', 'audit_admin', 'audit_remark', 'audit_time']);
            $table->tinyInteger('status')->default(1)->comment('1正常 0取消')->after('sign_info');
        });
    }

    public function down(): void
    {
        Schema::table('train_sign', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->tinyInteger('audit_status')->default(0)->comment('0待审核1通过2驳回')->after('sign_info');
            $table->bigInteger('audit_admin')->nullable()->after('audit_status');
            $table->string('audit_remark', 200)->nullable()->after('audit_admin');
            $table->dateTime('audit_time')->nullable()->after('audit_remark');
        });
    }
};
