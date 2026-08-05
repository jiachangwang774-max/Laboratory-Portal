<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseSession extends Model
{
    protected $table = 'course_sessions';
    protected $primaryKey = 'session_id';
    public $timestamps = false;

    protected $fillable = [
        'course_id', 'title', 'content', 'session_date', 'end_time',
        'location', 'instructor', 'status', 'sort_order',
        'create_admin', 'create_time', 'update_time',
    ];

    public function course()
    {
        return $this->belongsTo(TrainCourse::class, 'course_id', 'course_id');
    }

    /**
     * 只查询正常展示的课程安排
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1);
    }
}
