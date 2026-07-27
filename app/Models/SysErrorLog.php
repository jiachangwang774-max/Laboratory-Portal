<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysErrorLog extends Model
{
    protected $table = 'sys_error_log';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'trace_id',
        'level',
        'message',
        'exception_message',
        'exception_file',
        'exception_line',
        'exception_trace',
        'channel',
        'url',
        'method',
        'user_id',
        'ip',
        'context',
        'create_time',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}
