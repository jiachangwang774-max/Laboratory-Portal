<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LX\UserAuthController;
use App\Http\Controllers\LX\UserPwdResetController;

/*
|--------------------------------------------------------------------------
| API Routes — 实验室官网系统（用户端）
|--------------------------------------------------------------------------
|
| 前缀: /api/v1
| 鉴权: Authorization: Bearer {accessToken}
|
*/

// =======================================================================
// 前台用户端 /api/v1/user
// =======================================================================

// 公开路由（无需登录）
Route::prefix('user/auth')->group(function () {
    Route::post('/login',         [UserAuthController::class, 'login']);
    Route::post('/refresh_token', [UserAuthController::class, 'refreshToken']);
});

Route::prefix('user/pwd_reset')->group(function () {
    Route::post('/send_code', [UserPwdResetController::class, 'sendCode']);
    Route::post('/reset_pwd', [UserPwdResetController::class, 'resetPwd']);
});

// 需用户认证路由
Route::prefix('user/auth')->middleware('auth:user_api')->group(function () {
    Route::post('/logout',      [UserAuthController::class, 'logout']);
    Route::get('/info',         [UserAuthController::class, 'info']);
    Route::post('/update_info', [UserAuthController::class, 'updateInfo']);
    Route::post('/update_pwd',  [UserAuthController::class, 'updatePwd']);
});
