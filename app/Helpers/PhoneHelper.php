<?php

namespace App\Helpers;

/**
 * 手机号工具类
 *
 * 提供手机号清洗、格式校验、脱敏展示等功能。
 */
class PhoneHelper
{
    /**
     * 清洗手机号 —— 剔除空格、横杠、括号、+86 前缀等非数字字符。
     *
     * 示例：
     *   "+86 138-1234-5678"  →  "13812345678"
     *   " 138 1234 5678 "    →  "13812345678"
     */
    public static function clean(string $phone): string
    {
        // 移除 +86 / 86 前缀（含空格、横杠等变体）
        $phone = preg_replace('/^(\+?86[\s\-]?)/', '', trim($phone));

        // 只保留数字
        return preg_replace('/\D/', '', $phone);
    }

    /**
     * 脱敏展示：中间四位隐藏 → 138****5678
     */
    public static function mask(string $phone): string
    {
        $phone = self::clean($phone);

        if (mb_strlen($phone) !== 11) {
            return $phone;
        }

        return substr($phone, 0, 3) . '****' . substr($phone, 7, 4);
    }

    /**
     * 校验是否为中国大陆 11 位手机号（仅位数校验）。
     * 完整校验请使用 PhoneNumber 验证规则。
     */
    public static function isElevenDigits(string $phone): bool
    {
        return strlen(self::clean($phone)) === 11;
    }

    /**
     * 校验号段是否为运营商正规号段。
     */
    public static function isValidPrefix(string $phone): bool
    {
        $phone   = self::clean($phone);
        $prefix  = substr($phone, 0, 3);
        $prefixes = config('phone.valid_prefixes', []);

        return in_array($prefix, $prefixes, true);
    }

    /**
     * 检查是否为重复数字（如 11111111111）。
     */
    public static function isAllRepeated(string $phone): bool
    {
        $phone = self::clean($phone);

        if (strlen($phone) !== 11) {
            return false;
        }

        return count(array_unique(str_split($phone))) === 1;
    }

    /**
     * 检查是否为连续递增或递减的顺子号码。
     *
     * 例如：
     *   递增顺子: 12345678901 (相邻每一位 +1)
     *   递减顺子: 10987654321 (相邻每一位 -1)
     */
    public static function isSequential(string $phone): bool
    {
        $phone = self::clean($phone);

        if (strlen($phone) !== 11) {
            return false;
        }

        $digits = str_split($phone);

        $asc  = true;
        $desc = true;

        for ($i = 1; $i < 11; $i++) {
            $diff = (int) $digits[$i] - (int) $digits[$i - 1];

            // 递增：每一位 +1（考虑 9→0 回绕情况，这里只考虑纯递增）
            if ($diff !== 1) {
                $asc = false;
            }

            // 递减：每一位 -1
            if ($diff !== -1) {
                $desc = false;
            }
        }

        return $asc || $desc;
    }

    /**
     * 检查手机号是否在黑名单中。
     */
    public static function isBlacklisted(string $phone): bool
    {
        $phone     = self::clean($phone);
        $blacklist = config('phone.blacklist', []);

        return in_array($phone, $blacklist, true);
    }
}
