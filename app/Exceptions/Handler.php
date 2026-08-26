<?php

namespace App\Exceptions;

use Throwable;
use App\Services\DatabaseLogService;
use App\Support\Result;
use App\Enums\ResponseCode;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends \Illuminate\Foundation\Exceptions\Handler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * 统一异常渲染
     */
    public function render($request, Throwable $e)
    {
        // JWT Token 已过期
        if ($e instanceof TokenExpiredException) {
            return Result::error(
                ResponseCode::LOGIN_EXPIRED,
                '登录已过期，请重新登录'
            );
        }

        // JWT Token 无效
        if ($e instanceof TokenInvalidException) {
            return Result::error(
                ResponseCode::TOKEN_INVALID,
                '令牌无效'
            );
        }

        // JWT Token 已被加入黑名单
        if ($e instanceof TokenBlacklistedException) {
            return Result::error(
                ResponseCode::TOKEN_EXPIRED,
                '令牌已被注销'
            );
        }

        // JWT 通用异常
        if ($e instanceof JWTException) {
            return Result::error(
                ResponseCode::UNAUTHORIZED,
                '认证失败'
            );
        }

        // 参数验证异常
        if ($e instanceof ValidationException) {
            return Result::error(
                ResponseCode::PARAM_ERROR,
                collect($e->errors())->flatten()->first()
            );
        }

        // 未登录
        if ($e instanceof AuthenticationException) {
            return Result::error(
                ResponseCode::UNAUTHORIZED
            );
        }

        // 模型不存在
        if ($e instanceof ModelNotFoundException) {
            return Result::error(
                ResponseCode::DATA_NOT_FOUND
            );
        }

        // 路由不存在
        if ($e instanceof NotFoundHttpException) {
            return Result::error(
                ResponseCode::DATA_NOT_FOUND,
                '接口不存在'
            );
        }

        // 请求方法不支持
        if ($e instanceof MethodNotAllowedHttpException) {
            $allowed = $e->getHeaders()['Allow'] ?? null;
            return Result::error(
                ResponseCode::METHOD_NOT_ALLOWED,
                $allowed ? "请求方式不支持，允许: {$allowed}" : null
            );
        }

        // 业务异常
        if ($e instanceof BusinessException) {
            return Result::error(
                $e->codeEnum,
                $e->getMessage()
            );
        }

        // 数据库异常
        if ($e instanceof QueryException) {
            Log::channel('exception')->error('数据库异常', [
                'trace_id' => $request->attributes->get('trace_id'),
                'sql'      => $e->getSql(),
                'bindings' => $e->getBindings(),
                'message'  => $e->getMessage(),
            ]);

            DatabaseLogService::logError([
                'level'             => 'error',
                'message'           => '数据库异常',
                'exception_message' => $e->getMessage(),
                'exception_file'    => $e->getFile(),
                'exception_line'    => $e->getLine(),
                'exception_trace'   => $e->getTraceAsString(),
                'channel'           => 'exception',
                'context'           => ['sql' => $e->getSql(), 'bindings' => $e->getBindings()],
            ]);

            return Result::error(
                ResponseCode::DATABASE_ERROR
            );
        }

        // 系统异常日志
        Log::channel('exception')->error($e->getMessage(), [
            'trace_id' => $request->attributes->get('trace_id'),
            'file'     => $e->getFile(),
            'line'     => $e->getLine(),
            'trace'    => $e->getTraceAsString(),
        ]);

        DatabaseLogService::logError([
            'level'             => 'error',
            'message'           => '系统异常',
            'exception_message' => $e->getMessage(),
            'exception_file'    => $e->getFile(),
            'exception_line'    => $e->getLine(),
            'exception_trace'   => $e->getTraceAsString(),
            'channel'           => 'exception',
        ]);

        // 未知异常
        return Result::error(
            ResponseCode::SYSTEM_ERROR
        );
    }
}
