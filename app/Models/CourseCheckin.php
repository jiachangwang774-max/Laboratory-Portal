<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseCheckin extends Model
{
    protected $table = 'course_checkins';
    protected $primaryKey = 'checkin_id';
    public $timestamps = false;

    protected $fillable = [
        'course_id', 'session_id', 'checkin_code', 'status',
        'create_admin', 'lab_id', 'create_time', 'end_time',
    ];

    public function course()
    {
        return $this->belongsTo(TrainCourse::class, 'course_id', 'course_id');
    }

    public function session()
    {
        return $this->belongsTo(CourseSession::class, 'session_id', 'session_id');
    }

    public function records()
    {
        return $this->hasMany(CheckinRecord::class, 'checkin_id', 'checkin_id');
    }
}
