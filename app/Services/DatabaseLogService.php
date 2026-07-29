<?php

namespace App\Services;

use App\Models\SysOperationLog;
use App\Models\SysLoginLog;
use App\Models\SysErrorLog;
use Illuminate\Support\Facades\Log;

/**
 * 数据库日志服务
 *
 * 将操作日志、登录日志、错误日志写入对应的数据库表。
 * 所有写操作都包裹了 try-catch，避免日志写入异常影响正常业务。
 */
class DatabaseLogService
{
    /**
     * 记录操作日志 → sys_operation_log
     */
    public static function logOperation(array $data): void
    {
        try {
            $request = request();

            SysOperationLog::create(array_merge([
                'trace_id'      => $request->attributes->get('trace_id'),
                'ip'            => $request->ip(),
                'user_agent'    => $request->userAgent(),
                'create_time'   => now(),
            ], $data));
        } catch (\Throwable $e) {
            Log::error('操作日志入库失败: ' . $e->getMessage());
        }
    }

    /**
     * 记录登录日志 → sys_login_log
     */
    public static function logLogin(array $data): void
    {
        try {
            $request = request();

            SysLoginLog::create(array_merge([
                'trace_id'    => $request->attributes->get('trace_id'),
                'ip'          => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'login_time'  => now(),
            ], $data));
        } catch (\Throwable $e) {
            Log::error('登录日志入库失败: ' . $e->getMessage());
        }
    }

    /**
     * 记录错误日志 → sys_error_log
     */
    public static function logError(array $data): void
    {
        try {
            $request = request();

            SysErrorLog::create(array_merge([
                'trace_id'    => $request->attributes->get('trace_id'),
                'url'         => $request->fullUrl(),
                'method'      => $request->method(),
                'ip'          => $request->ip(),
                'create_time' => now(),
            ], $data));
        } catch (\Throwable $e) {
            Log::error('错误日志入库失败: ' . $e->getMessage());
        }
    }
}
