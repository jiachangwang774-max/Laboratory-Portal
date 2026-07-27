<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * 成功响应
     */
    public static function success($data = [], string $msg = '操作成功', int $code = 200): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ]);
    }

    /**
     * 错误响应
     */
    public static function error(string $msg = '操作失败', int $code = 500, $data = []): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ]);
    }

    /**
     * 未授权（401）
     */
    public static function unauthorized(string $msg = '未授权，请重新登录'): JsonResponse
    {
        return static::error($msg, 401);
    }

    /**
     * 无权限（403）
     */
    public static function forbidden(string $msg = '无权限访问'): JsonResponse
    {
        return static::error($msg, 403);
    }
}
