<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TraceId
{
    /**
     * 为每个请求生成唯一 trace_id，用于全链路追踪。
     */
    public function handle(Request $request, Closure $next)
    {
        $traceId = (string) Str::uuid();

        // 注入到 Log 共享上下文，所有日志自动携带
        \Log::withContext([
            'trace_id' => $traceId,
        ]);

        // 同时挂到 Request 上，方便 Service 层获取
        $request->attributes->set('trace_id', $traceId);

        $response = $next($request);

        // 响应头返回 trace_id，方便前端排查
        $response->headers->set('X-Trace-Id', $traceId);

        return $response;
    }
}
