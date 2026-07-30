<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckinRecord extends Model
{
    protected $table = 'checkin_records';
    protected $primaryKey = 'record_id';
    public $timestamps = false;

    protected $fillable = [
        'checkin_id', 'user_id', 'checkin_method', 'checkin_time',
    ];

    public function user()
    {
        return $this->belongsTo(SysUser::class, 'user_id', 'user_id');
    }
}
