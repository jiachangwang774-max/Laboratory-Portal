<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
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

        // JWT Token 已过期
        $this->renderable(function (TokenExpiredException $e) {
            return ApiResponse::unauthorized('登录已过期，请重新登录');
        });

        // JWT Token 无效
        $this->renderable(function (TokenInvalidException $e) {
            return ApiResponse::unauthorized('令牌无效');
        });

        // JWT Token 已被加入黑名单
        $this->renderable(function (TokenBlacklistedException $e) {
            return ApiResponse::unauthorized('令牌已被注销');
        });

        // JWT 通用异常
        $this->renderable(function (JWTException $e) {
            return ApiResponse::unauthorized('认证失败');
        });

        // 未认证（未携带 Token）
        $this->renderable(function (AuthenticationException $e) {
            return ApiResponse::unauthorized('未登录，请先登录');
        });
    }
}
