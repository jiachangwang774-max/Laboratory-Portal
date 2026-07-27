<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainSign extends Model
{
    protected $table = 'train_sign';
    protected $primaryKey = 'sign_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',//用户ID
        'course_id',//课程ID
        'sign_info',//报名信息
        'audit_status',//审核状态
        'audit_admin',//审核人ID
        'audit_remark',//审核备注
        'sign_time',//报名时间
        'audit_time',//审核时间
    ];

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(TrainCourse::class, 'course_id', 'course_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(SysUser::class, 'user_id', 'user_id');
    }
}
