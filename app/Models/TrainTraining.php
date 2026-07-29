<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainTraining extends Model
{
    protected $table = 'train_training';
    protected $primaryKey = 'training_id';

    public $timestamps = false;

    protected $fillable = [
        'training_time',//培训时间
        'training_content',//培训内容
        'status',//状态
        'create_admin',//创建人ID
        'create_time',//创建时间
    ];

    /**
     * 只查询正常展示的培训
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1);
    }
}
