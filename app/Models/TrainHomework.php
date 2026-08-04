<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainHomework extends Model
{
    protected $table = 'train_homework';
    protected $primaryKey = 'homework_id';

    public $timestamps = false;

    protected $fillable = [
        'course_id',//课程ID
        'homework_title',//作业标题
        'homework_content',//作业内容
        'deadline',//截止时间
        'group_name',//指定班级
        'create_time',//创建时间
    ];

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(TrainCourse::class, 'course_id', 'course_id');
    }
}
