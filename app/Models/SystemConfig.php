<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $table = 'system_config';
    protected $primaryKey = 'config_id';

    public $timestamps = false;

    protected $fillable = [
        'config_key',//配置键
        'config_value',//配置值
        'remark',//备注
    ];

    /**
     * 根据 key 获取配置值
     */
    public static function getValue(string $key, $default = null): ?string
    {
        $config = static::where('config_key', $key)->first();
        return $config ? $config->config_value : $default;
    }
}
