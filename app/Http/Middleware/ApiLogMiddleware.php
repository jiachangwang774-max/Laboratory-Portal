<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiLogMiddleware
{
    /**
     * 记录所有 API 请求的：URL、方法、参数、耗时、状态码。
     */
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        // 过滤敏感信息
        $input = $this->filterSensitive($request->all());

        Log::channel('api')->info('API Request', [
            'url'             => $request->fullUrl(),
            'method'          => $request->method(),
            'ip'              => $request->ip(),
            'user_id'         => optional(auth('user_api')->user())->user_id,
            'request'         => $input,
            'response_status' => $response->status(),
            'duration_ms'     => $duration,
        ]);

        // 慢接口告警（超过 1 秒）
        if ($duration > 1000) {
            Log::channel('business')->warning('慢接口告警', [
                'url'          => $request->fullUrl(),
                'method'       => $request->method(),
                'duration_ms'  => $duration,
            ]);
        }

        return $response;
    }

    /**
     * 过滤敏感字段，不写入日志。
     */
    protected function filterSensitive(array $data): array
    {
        $sensitive = ['password', 'password_confirmation', 'token', 'secret', 'id_card', 'oldPwd', 'newPwd', 'old_pwd', 'new_pwd'];

        foreach ($sensitive as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***';
            }
        }

        return $data;
    }
}
