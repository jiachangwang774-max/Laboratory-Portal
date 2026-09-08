<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(600)->by($request->user()?->id ?: $request->ip());
        });

        // 登录限流：按 IP 每分钟 10 次（防暴力破解，三四百不同来源用户互不影响）
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // 注册限流：按 IP 每分钟 5 次
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // 发送验证码限流：按 IP 每分钟 3 次（防刷邮件）
        RateLimiter::for('verify_code', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
