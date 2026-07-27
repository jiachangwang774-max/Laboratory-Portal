<?php

namespace App\Helpers;

/**
 * 密码安全工具类
 *
 * 提供密码强度检测、弱密码字典匹配、连续序列检测等功能。
 */
class PasswordHelper
{
    /**
     * 检查是否在弱密码字典中（不区分大小写）。
     */
    public static function isWeakDictionary(string $password): bool
    {
        $dictionary = config('password.weak_password_dictionary', []);
        $lower      = mb_strtolower($password);

        foreach ($dictionary as $weak) {
            if (mb_strtolower($weak) === $lower) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否为纯数字。
     */
    public static function isAllDigits(string $password): bool
    {
        return ctype_digit($password);
    }

    /**
     * 检查是否为纯字母（含大小写）。
     */
    public static function isAllLetters(string $password): bool
    {
        return ctype_alpha($password);
    }

    /**
     * 检查是否包含连续有序字符（递增或递减）。
     *
     * 滑动窗口检测，默认窗口 = 3。
     * 例如 "abc"、"123"、"cba"、"321" 等。
     * 窗口越长越容易被判定为弱密码。
     */
    public static function hasSequentialChars(string $password, int $window = 3): bool
    {
        $len = mb_strlen($password);

        if ($len < $window) {
            return false;
        }

        for ($i = 0; $i <= $len - $window; $i++) {
            $asc  = true;
            $desc = true;

            for ($j = 1; $j < $window; $j++) {
                $curr = mb_ord(mb_substr($password, $i + $j, 1));
                $prev = mb_ord(mb_substr($password, $i + $j - 1, 1));

                if ($curr !== $prev + 1) {
                    $asc = false;
                }
                if ($curr !== $prev - 1) {
                    $desc = false;
                }
            }

            if ($asc || $desc) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否包含非法字符。
     *
     * 非法字符包括：空格、中文、表情符号、引号、反斜杠等。
     *
     * @return string|null 返回命中的非法字符描述，未命中返回 null
     */
    public static function detectIllegalChars(string $password): ?string
    {
        // 空格
        if (preg_match('/\s/u', $password)) {
            return '空格';
        }

        // 中文
        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $password)) {
            return '中文字符';
        }

        // 表情符号（Unicode emoji 范围）
        if (preg_match('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $password)) {
            return '表情符号';
        }

        // 引号
        if (preg_match('/[\'"]/', $password)) {
            return '引号';
        }

        // 反斜杠
        if (str_contains($password, '\\')) {
            return '反斜杠';
        }

        return null;
    }

    /**
     * 检查是否包含非允许的特殊符号。
     *
     * @return string|null 返回命中的非法符号，未命中返回 null
     */
    public static function detectDisallowedSymbols(string $password): ?string
    {
        $allowed = config('password.allowed_symbols', '!@#$%^&*()_+-=[]{}|;:,.?~');

        // 提取所有非字母数字的字符
        $symbols = preg_replace('/[a-zA-Z0-9]/', '', $password);

        if (empty($symbols)) {
            return null;
        }

        $allowedChars = str_split($allowed);
        $symbolChars  = str_split($symbols);

        $disallowed = array_diff($symbolChars, $allowedChars);

        if (!empty($disallowed)) {
            return implode(' ', array_unique($disallowed));
        }

        return null;
    }

    /**
     * 检查密码是否包含指定内容（不区分大小写）。
     *
     * 用于拦截密码中包含用户名、手机号等个人信息的场景。
     *
     * @param string $password 待检测密码
     * @param string $context  要匹配的上下文内容
     * @return bool 是否包含
     */
    public static function containsContext(string $password, string $context): bool
    {
        if (empty($context)) {
            return false;
        }

        return mb_stripos($password, $context) !== false;
    }

    /**
     * 检查是否包含字母。
     */
    public static function containsLetter(string $password): bool
    {
        return (bool) preg_match('/[a-zA-Z]/', $password);
    }

    /**
     * 检查是否包含数字。
     */
    public static function containsNumber(string $password): bool
    {
        return (bool) preg_match('/[0-9]/', $password);
    }

    /**
     * 检查是否包含允许的特殊符号。
     */
    public static function containsAllowedSymbol(string $password): bool
    {
        $allowed = config('password.allowed_symbols', '!@#$%^&*()_+-=[]{}|;:,.?~');

        // 对每个特殊符号进行转义后构建正则
        $escaped = implode('', array_map(fn($c) => '\\' . $c, str_split($allowed)));

        return (bool) preg_match('/[' . $escaped . ']/', $password);
    }
}
