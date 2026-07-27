<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysLoginLog extends Model
{
    protected $table = 'sys_login_log';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'trace_id',
        'login_type',
        'user_id',
        'username',
        'status',
        'fail_reason',
        'ip',
        'user_agent',
        'login_time',
    ];
}
