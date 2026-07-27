<?php

namespace App\Support;

use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;

/**
 * 统一响应类
 *
 * 所有接口必须通过 Result 返回数据
 */
class Result
{
    /**
     * 成功响应
     */
    public static function success(
        string $msg = '成功',
        mixed $data = null
    ): JsonResponse {
        return response()->json([
            'code'    => ResponseCode::SUCCESS->value,
            'msg'     => $msg,
            'data'    => $data,
            'success' => true,
            'trace_id' => request()->attributes->get('trace_id'),
        ]);
    }

    /**
     * 失败响应
     */
    public static function error(
        ResponseCode $code,
        ?string $msg = null,
        mixed $data = null
    ): JsonResponse {
        return response()->json([
            'code'    => $code->value,
            'msg'     => $msg ?? $code->msg(),
            'data'    => $data,
            'success' => false,
            'trace_id' => request()->attributes->get('trace_id'),
        ]);
    }
}
