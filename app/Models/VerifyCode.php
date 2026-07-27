<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifyCode extends Model
{
    protected $table = 'verify_code';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'target',
        'code',
        'type',
        'expire_time',
        'create_time',
    ];
}
