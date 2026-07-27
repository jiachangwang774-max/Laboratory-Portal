<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysErrorLog extends Model
{
    protected $table = 'sys_error_log';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'trace_id',//跟踪ID
        'level',//级别
        'message',//消息
        'exception_message',//异常消息
        'exception_file',//异常文件
        'exception_line',//异常行号
        'exception_trace',//异常跟踪
        'channel',//渠道
        'url',//URL
        'method',//方法
        'user_id',//用户ID
        'ip',//IP
        'context',//上下文
        'create_time',//创建时间
    ];

    protected $casts = [
        'context' => 'array',
    ];
}
