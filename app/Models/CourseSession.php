<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseSession extends Model
{
    protected $table = 'course_sessions';
    protected $primaryKey = 'session_id';

    public $timestamps = false;

    protected $fillable = [
        'course_id',   // 所属课程ID
        'title',       // 标题
        'content',     // 内容
        'session_date',// 日期
        'end_time',    // 结束时间
        'location',    // 地点
        'instructor',  // 讲师
        'status',      // 状态 1正常 0下架
        'sort_order',  // 排序
        'create_admin',// 创建人
        'create_time', // 创建时间
    ];

    /**
     * 只查询正常展示的
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1);
    }

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(TrainCourse::class, 'course_id', 'course_id');
    }
}
