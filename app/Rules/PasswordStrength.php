<?php

namespace App\Rules;

use App\Helpers\PhoneHelper;
use App\Helpers\PasswordHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 密码强度验证规则
 *
 * 校验链路：
 *   长度8~20 → 包含字母+数字+符号 → 无非法字符 → 符号白名单
 *   → 非纯数字/纯字母 → 无连续序列 → 非弱密码字典
 *   → 不含用户名/手机号等关联信息
 */
class PasswordStrength implements ValidationRule
{
    /**
     * @param array{username?: string, phone?: string} $context
     */
    public function __construct(
        protected array $context = [],
    ) {}

    /**
     * 执行校验。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;

        $min = config('password.min_length', 8);
        $max = config('password.max_length', 20);
        $len = mb_strlen($password);

        // 1. 长度校验
        if ($len < $min || $len > $max) {
            $fail("密码长度必须在 {$min}~{$max} 位之间");

            return;
        }

        // 2. 必须包含字母
        if (config('password.require_letter', true) && !PasswordHelper::containsLetter($password)) {
            $fail('密码必须包含字母');

            return;
        }

        // 3. 必须包含数字
        if (config('password.require_number', true) && !PasswordHelper::containsNumber($password)) {
            $fail('密码必须包含数字');

            return;
        }

        // 4. 必须包含特殊符号
        if (config('password.require_symbol', true) && !PasswordHelper::containsAllowedSymbol($password)) {
            $fail('密码必须包含特殊符号（如 !@#$% 等）');

            return;
        }

        // 5. 非法字符检测（空格、中文、表情、引号、反斜杠）
        $illegal = PasswordHelper::detectIllegalChars($password);
        if ($illegal !== null) {
            $fail("密码不允许包含{$illegal}");

            return;
        }

        // 6. 非允许符号检测
        $disallowed = PasswordHelper::detectDisallowedSymbols($password);
        if ($disallowed !== null) {
            $fail("密码包含不允许的特殊符号：{$disallowed}");

            return;
        }

        // 7. 纯数字拦截
        if (config('password.reject_pure_number', true) && PasswordHelper::isAllDigits($password)) {
            $fail('密码不能为纯数字');

            return;
        }

        // 8. 纯字母拦截
        if (config('password.reject_pure_letter', true) && PasswordHelper::isAllLetters($password)) {
            $fail('密码不能为纯字母');

            return;
        }

        // 9. 连续有序字符拦截（如 123456、abcdef、654321）
        if (config('password.reject_sequential', true) && PasswordHelper::hasSequentialChars($password)) {
            $fail('密码不允许包含连续有序字符（如 123、abc）');

            return;
        }

        // 10. 弱密码字典匹配（大小写不敏感）
        if (PasswordHelper::isWeakDictionary($password)) {
            $fail('该密码过于简单，请更换其他密码');

            return;
        }

        // 11. 账号关联信息检测
        if (config('password.reject_context_related', true)) {
            // 检查是否包含用户名
            if (!empty($this->context['username'])) {
                if (PasswordHelper::containsContext($password, $this->context['username'])) {
                    $fail('密码不能包含用户名');

                    return;
                }
            }

            // 检查是否包含完整手机号
            if (!empty($this->context['phone'])) {
                $cleanPhone = PhoneHelper::clean($this->context['phone']);
                if (mb_strlen($cleanPhone) === 11 && PasswordHelper::containsContext($password, $cleanPhone)) {
                    $fail('密码不能包含手机号');

                    return;
                }

                // 检查是否包含手机号后6位
                if (mb_strlen($cleanPhone) >= 6) {
                    $last6 = substr($cleanPhone, -6);
                    if (PasswordHelper::containsContext($password, $last6)) {
                        $fail('密码不能包含手机号后6位');

                        return;
                    }
                }
            }
        }
    }
}
