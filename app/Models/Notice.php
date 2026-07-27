<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $table = 'notice';
    protected $primaryKey = 'notice_id';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'content',
        'cover',
        'is_top',
        'status',
        'create_admin',
        'create_time',
        'update_time',
    ];

    /**
     * 只查询正常展示的公告
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1);
    }
}
