<?php

namespace App\Exceptions;

use App\Enums\ResponseCode;
use RuntimeException;

/**
 * 业务异常
 *
 * Service 层遇到业务规则不满足时，主动抛出此异常
 */
class BusinessException extends RuntimeException
{
    /**
     * @param string $message 异常描述
     * @param ResponseCode $codeEnum 错误码枚举
     */
    public function __construct(
        string $message = '业务处理失败',
        public readonly ResponseCode $codeEnum = ResponseCode::BUSINESS_ERROR,
    ) {
        parent::__construct($message);
    }
}
