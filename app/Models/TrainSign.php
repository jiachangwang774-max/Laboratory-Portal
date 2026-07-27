<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainSign extends Model
{
    protected $table = 'train_sign';
    protected $primaryKey = 'sign_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'course_id',
        'sign_info',
        'audit_status',
        'audit_admin',
        'audit_remark',
        'sign_time',
        'audit_time',
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
