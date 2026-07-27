<?php

namespace App\Enums;

enum ResponseCode: int
{
    /**
     * 成功
     */
    case SUCCESS = 0;

    /**
     * 参数异常
     */
    case PARAM_ERROR = 10001;

    /**
     * 未登录
     */
    case UNAUTHORIZED = 20001;

    /**
     * Token 失效
     */
    case TOKEN_EXPIRED = 20002;

    /**
     * Token 错误
     */
    case TOKEN_INVALID = 20003;

    /**
     * 登录已过期
     */
    case LOGIN_EXPIRED = 20004;

    /**
     * 无权限
     */
    case FORBIDDEN = 20005;

    /**
     * 账号被禁用
     */
    case ACCOUNT_DISABLED = 20006;

    /**
     * 密码错误
     */
    case PASSWORD_ERROR = 20008;

    /**
     * 数据不存在
     */
    case DATA_NOT_FOUND = 30001;

    /**
     * 数据重复
     */
    case DATA_DUPLICATE = 30003;

    /**
     * 用户已存在
     */
    case USER_ALREADY_EXISTS = 30004;

    /**
     * 业务异常
     */
    case BUSINESS_ERROR = 40001;

    /**
     * 验证码错误或过期
     */
    case VERIFY_CODE_ERROR = 40002;

    /**
     * 重复提交
     */
    case DUPLICATE_SUBMIT = 40009;

    /**
     * 第三方接口异常
     */
    case THIRD_PARTY_ERROR = 50001;

    /**
     * 短信发送失败
     */
    case SMS_SEND_FAILED = 50003;

    /**
     * 数据库异常
     */
    case DATABASE_ERROR = 60001;

    /**
     * 唯一索引冲突
     */
    case UNIQUE_CONFLICT = 60005;

    /**
     * 系统异常
     */
    case SYSTEM_ERROR = 90001;

    /**
     * 获取每个错误码对应的 HTTP 状态码
     */
    public function httpStatusCode(): int
    {
        return match ($this) {
            self::SUCCESS => 200,

            // 1xxxx 参数异常 → 422 Unprocessable Entity
            self::PARAM_ERROR => 422,

            // 2xxxx 认证/授权异常
            self::UNAUTHORIZED     => 401,
            self::TOKEN_EXPIRED    => 401,
            self::TOKEN_INVALID    => 401,
            self::LOGIN_EXPIRED    => 401,
            self::FORBIDDEN        => 403,
            self::ACCOUNT_DISABLED => 403,
            self::PASSWORD_ERROR   => 401,

            // 3xxxx 数据异常
            self::DATA_NOT_FOUND      => 404,
            self::DATA_DUPLICATE      => 409,
            self::USER_ALREADY_EXISTS => 409,

            // 4xxxx 业务异常
            self::BUSINESS_ERROR    => 400,
            self::VERIFY_CODE_ERROR => 400,
            self::DUPLICATE_SUBMIT  => 429,

            // 5xxxx 第三方服务异常
            self::THIRD_PARTY_ERROR => 502,
            self::SMS_SEND_FAILED   => 500,

            // 6xxxx 数据库/系统异常 → 500
            self::DATABASE_ERROR  => 500,
            self::UNIQUE_CONFLICT => 409,

            self::SYSTEM_ERROR => 500,
        };
    }

    public function msg(): string
    {
        return match ($this) {
            self::SUCCESS          => '成功',

            self::PARAM_ERROR      => '参数错误',

            self::UNAUTHORIZED     => '未登录',
            self::TOKEN_EXPIRED    => 'Token 已失效',
            self::TOKEN_INVALID    => 'Token 无效',
            self::LOGIN_EXPIRED    => '登录已过期',
            self::FORBIDDEN        => '无权限访问',
            self::ACCOUNT_DISABLED => '账号已被禁用',
            self::PASSWORD_ERROR   => '密码错误',

            self::DATA_NOT_FOUND      => '记录不存在',
            self::DATA_DUPLICATE      => '数据重复',
            self::USER_ALREADY_EXISTS => '用户已存在',

            self::BUSINESS_ERROR    => '业务处理失败',
            self::VERIFY_CODE_ERROR => '验证码错误或已过期',
            self::DUPLICATE_SUBMIT  => '请勿重复提交',

            self::THIRD_PARTY_ERROR => '第三方服务异常',
            self::SMS_SEND_FAILED   => '短信发送失败',

            self::DATABASE_ERROR   => '数据库异常',
            self::UNIQUE_CONFLICT  => '数据重复，请检查后提交',

            self::SYSTEM_ERROR     => '系统异常',
        };
    }
}
