<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysPasswordHistory extends Model
{
    protected $table = 'sys_password_history';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',//用户ID
        'password_hash',//密码哈希值
        'create_time',//创建时间
    ];
}
