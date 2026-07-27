<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainHomework extends Model
{
    protected $table = 'train_homework';
    protected $primaryKey = 'homework_id';

    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'homework_title',
        'homework_content',
        'deadline',
        'create_time',
    ];

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(TrainCourse::class, 'course_id', 'course_id');
    }
}
