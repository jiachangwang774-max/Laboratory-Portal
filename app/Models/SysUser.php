<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class SysUser extends Authenticatable implements JWTSubject
{
    protected $table = 'sys_user';
    protected $primaryKey = 'user_id';

    // 数据库层使用 useCurrent() / useCurrentOnUpdate() 管理时间戳
    public $timestamps = false;

    protected $fillable = [
        'username',//用户名
        'password',//密码
        'real_name',//真实姓名
        'phone',//手机号
        'email',//邮箱
        'avatar',//头像
        'grade',//年级
        'major',//专业
        'college',//学院
        'student_id',//学号
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
