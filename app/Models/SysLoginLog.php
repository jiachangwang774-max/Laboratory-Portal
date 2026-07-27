<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysLoginLog extends Model
{
    protected $table = 'sys_login_log';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'trace_id',//跟踪ID
        'login_type',//登录类型
        'user_id',//用户ID
        'username',//用户名
        'status',//状态
        'fail_reason',//失败原因
        'ip',//IP
        'user_agent',//用户代理
        'login_time',//登录时间
    ];
}
