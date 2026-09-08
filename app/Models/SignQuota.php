<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignQuota extends Model
{
    protected $table = 'sign_quota';

    // 单行表，主键恒为 1，非自增
    public $incrementing = false;

    // 数据库层使用 useCurrent() / useCurrentOnUpdate() 管理时间戳
    public $timestamps = false;

    protected $fillable = [
        'limit_count', // 报名人数上限
        'remark',      // 备注
    ];
}
