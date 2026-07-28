<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\SystemConfig;
use App\Traits\LogTrait;

class SignSwitchService
{
    use LogTrait;

    /**
     * 查询报名开关（后台）
     */
    public function get(): array
    {
        $value = (int) SystemConfig::getValue('train_sign_switch', '1');

        return [
            'value' => $value,
            'text'  => $value === 1 ? '报名功能已开启' : '报名功能已关闭',
        ];
    }

    /**
     * 修改报名开关
     */
    public function update(int $value): array
    {
        SystemConfig::updateOrCreate(
            ['config_key' => 'train_sign_switch'],
            ['config_value' => (string) $value, 'remark' => '全局培训报名总开关']
        );

        $this->logBusiness('管理员修改报名开关', ['value' => $value]);

        return [
            'value' => $value,
            'text'  => $value === 1 ? '报名功能已开启' : '报名功能已关闭',
        ];
    }

    /**
     * 前台查询报名开关（公开）
     */
    public function frontGet(): array
    {
        $value = (int) SystemConfig::getValue('train_sign_switch', '1');

        return [
            'value' => $value,
            'tips'  => $value === 1 ? '当前培训报名开放中' : '当前培训报名已关闭',
        ];
    }
}
