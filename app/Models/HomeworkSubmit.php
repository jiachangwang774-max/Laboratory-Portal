<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkSubmit extends Model
{
    protected $table = 'homework_submit';
    protected $primaryKey = 'submit_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'homework_id',
        'submit_content',
        'submit_file',
        'submit_time',
        'score',
    ];

    /**
     * 关联作业
     */
    public function homework()
    {
        return $this->belongsTo(TrainHomework::class, 'homework_id', 'homework_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(SysUser::class, 'user_id', 'user_id');
    }
}
