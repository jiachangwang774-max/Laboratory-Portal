<?php

namespace App\Rules;

use App\Helpers\PhoneHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 中国大陆手机号验证规则
 *
 * 校验链路：清洗 → 11位 → 纯数字 → 非全重复 → 非顺子 → 号段合法 → 非黑名单
 */
class PhoneNumber implements ValidationRule
{
    /**
     * 校验失败时统一在此返回对应中文提示。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = PhoneHelper::clean((string) $value);

        // 1. 长度校验
        if (strlen($phone) !== 11) {
            $fail('手机号必须为11位数字');

            return;
        }

        // 2. 纯数字校验（清洗后若仍含非数字字符）
        if (!ctype_digit($phone)) {
            $fail('手机号只能包含数字');

            return;
        }

        // 3. 全重复数字拦截（如 11111111111）
        if (PhoneHelper::isAllRepeated($phone)) {
            $fail('手机号格式不正确');

            return;
        }

        // 4. 连续递增/递减顺子拦截（如 12345678901）
        if (PhoneHelper::isSequential($phone)) {
            $fail('手机号格式不正确');

            return;
        }

        // 5. 运营商号段校验
        if (!PhoneHelper::isValidPrefix($phone)) {
            $fail('手机号号段不在允许范围内');

            return;
        }

        // 6. 黑名单拦截
        if (PhoneHelper::isBlacklisted($phone)) {
            $fail('该手机号暂不支持注册');

            return;
        }
    }
}
