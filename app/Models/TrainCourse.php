<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainCourse extends Model
{
    protected $table = 'train_course';
    protected $primaryKey = 'course_id';

    public $timestamps = false;

    protected $fillable = [
        'course_name',
        'course_desc',
        'cover_img',
        'start_time',
        'end_time',
        'max_sign',
        'status',
        'create_admin',
        'create_time',
    ];

    /**
     * 只查询正常展示的课程
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1);
    }
}
