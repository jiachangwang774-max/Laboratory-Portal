<?php

namespace App\Enums;

/**
 * 验证码类型枚举
 *
 * 对应 verify_code 表的 type 字段
 */
enum VerifyCodeType: int
{
    /**
     * 重置密码
     */
    case PWD_RESET = 1;

    /**
     * 注销账号
     */
    case DELETE_ACCOUNT = 2;

    /**
     * 用户注册
     */
    case REGISTER = 3;

    /**
     * 获取验证码用途描述
     */
    public function label(): string
    {
        return match ($this) {
            self::PWD_RESET      => '重置密码',
            self::DELETE_ACCOUNT => '注销账号',
            self::REGISTER       => '注册',
        };
    }

    /**
     * 获取验证码用途对应的邮件标题后缀
     */
    public function mailTitle(): string
    {
        return match ($this) {
            self::PWD_RESET      => '重置密码',
            self::DELETE_ACCOUNT => '注销账号',
            self::REGISTER       => '注册',
        };
    }
}
