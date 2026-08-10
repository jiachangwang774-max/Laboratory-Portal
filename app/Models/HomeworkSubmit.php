<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkSubmit extends Model
{
    protected $table = 'homework_submit';
    protected $primaryKey = 'submit_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',//用户ID
        'homework_id',//作业ID
        'submit_content',//提交内容
        'submit_file',//提交文件
        'submit_time',//提交时间
        'score',//成绩
        'lab_id',//实验室
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
