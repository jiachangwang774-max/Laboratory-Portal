<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 给报名申请表加审核字段
     */
    public function up(): void
    {
        Schema::table('sign_application', function (Blueprint $table) {
            $table->tinyInteger('audit_status')->default(0)->comment('0待审核 1通过 2驳回')->after('status');
            $table->unsignedBigInteger('audit_admin')->nullable()->comment('审核管理员ID')->after('audit_status');
            $table->string('audit_remark', 200)->nullable()->comment('审核备注')->after('audit_admin');
            $table->dateTime('audit_time')->nullable()->comment('审核时间')->after('audit_remark');
        });
    }

    public function down(): void
    {
        Schema::table('sign_application', function (Blueprint $table) {
            $table->dropColumn(['audit_status', 'audit_admin', 'audit_remark', 'audit_time']);
        });
    }
};
