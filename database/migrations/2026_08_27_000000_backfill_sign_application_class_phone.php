<?php

// 数据修复：回填培训名单（sign_application）的班级与手机号
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 历史由「管理员新增学员」产生的 sign_application 记录，syncTrainRoster 只写了
        // college/major，漏了 class_name/phone，导致培训名单里班级、手机号为空。
        // 这里按学号（student_id）关联 sys_user 补齐：
        //   - phone：直接取 sys_user.phone
        //   - class_name：历史前端把「学校班级」发在了 grade 字段，故取 sys_user.grade
        // 仅回填当前为 NULL 的记录；学员自助报名（class_name/phone 必填）不会受影响。
        DB::statement(
            <<<'SQL'
            UPDATE sign_application sa
            JOIN sys_user u ON u.student_id = sa.student_id
            SET sa.phone = u.phone
            WHERE sa.phone IS NULL
              AND u.phone IS NOT NULL
              AND u.student_id IS NOT NULL
            SQL
        );

        DB::statement(
            <<<'SQL'
            UPDATE sign_application sa
            JOIN sys_user u ON u.student_id = sa.student_id
            SET sa.class_name = u.grade
            WHERE sa.class_name IS NULL
              AND u.grade IS NOT NULL
              AND u.student_id IS NOT NULL
            SQL
        );
    }

    public function down(): void
    {
        // 数据回填为单向修复，无法可靠还原原始 NULL 值，故留空。
    }
};
