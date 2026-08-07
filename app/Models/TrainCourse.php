<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainCourse extends Model
{
    protected $table = 'train_course';
    protected $primaryKey = 'course_id';

    public $timestamps = false;

    protected $fillable = [
        'course_name',//课程名称
        'course_desc',//课程描述
        'instructor',//讲师
        'course_date',//课程日期
        'location',//上课地点
        'cover_img',//封面图片
        'start_time',//开始时间
        'end_time',//结束时间
        'max_sign',//最大报名人数
        'group_count',//分班数量
        'group_name',//指定班级
        'lab_id',//实验室
        'status',//状态
        'create_admin',//创建人ID
        'create_time',//创建时间
    ];

    /**
     * 只查询正常展示的课程
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1);
    }

    /**
     * 关联创建管理员
     */
    public function admin()
    {
        return $this->belongsTo(SysAdmin::class, 'create_admin', 'admin_id');
    }
}
