<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignApplication extends Model
{
    protected $table = 'sign_application';

    // 数据库层使用 useCurrent() / useCurrentOnUpdate() 管理时间戳
    public $timestamps = false;

    protected $fillable = [
        'user_id',           // 用户ID（可选，未登录也可报名）
        'name',              // 姓名
        'student_id',        // 学号
        'department',        // 报名部门 1软件开发实验室 2人工智能实验室
        'college',           // 学院
        'major',             // 专业
        'class_name',        // 班级
        'phone',             // 手机号
        'self_introduction', // 自我介绍
        'status',            // 0草稿 1已提交
        'submit_time',       // 提交时间
        'audit_status',      // 0待审核 1通过 2驳回
        'group_name',        // 分班名称
        'audit_admin',       // 审核管理员ID
        'audit_remark',      // 审核备注
        'audit_time',        // 审核时间
    ];

    /**
     * 关联审核管理员
     */
    public function admin()
    {
        return $this->belongsTo(SysAdmin::class, 'audit_admin', 'admin_id');
    }
}
