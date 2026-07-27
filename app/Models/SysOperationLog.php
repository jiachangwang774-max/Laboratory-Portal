<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysOperationLog extends Model
{
    protected $table = 'sys_operation_log';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'trace_id',
        'module',
        'action',
        'operator_type',
        'operator_id',
        'operator_name',
        'target_type',
        'target_id',
        'before_data',
        'after_data',
        'ip',
        'user_agent',
        'create_time',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data'  => 'array',
    ];
}
