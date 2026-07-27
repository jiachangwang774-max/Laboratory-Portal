<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class SysAdmin extends Authenticatable implements JWTSubject
{
    protected $table = 'sys_admin';
    protected $primaryKey = 'admin_id';

    // 数据库层使用 useCurrent() / useCurrentOnUpdate() 管理时间戳
    public $timestamps = false;

    protected $fillable = [
        'admin_name',//管理员名称
        'password',//密码
        'real_name',//真实姓名
        'phone',//手机号
        'email',//邮箱
        'status',//状态
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the identifier that will be stored in the JWT subject claim.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
