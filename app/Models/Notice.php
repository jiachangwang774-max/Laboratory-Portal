<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $table = 'notice';
    protected $primaryKey = 'notice_id';

    public $timestamps = false;

    protected $fillable = [
        'title',//标题
        'content',//内容
        'cover',//封面
        'is_top',//是否置顶
        'status',//状态
        'create_admin',//创建管理员
        'create_time',//创建时间
        'update_time',//更新时间
    ];

    /**
     * 只查询正常展示的公告
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1);
    }
}
