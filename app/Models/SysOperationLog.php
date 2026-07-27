<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysOperationLog extends Model
{
    protected $table = 'sys_operation_log';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'trace_id',//跟踪ID
        'module',//模块
        'action',//操作
        'operator_type',//操作类型
        'operator_id',//操作人ID
        'operator_name',//操作人姓名
        'target_type',//目标类型
        'target_id',//目标ID
        'before_data',//操作前数据
        'after_data',//操作后数据
        'ip',//IP
        'user_agent',//用户代理
        'create_time',//创建时间
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data'  => 'array',
    ];
}
