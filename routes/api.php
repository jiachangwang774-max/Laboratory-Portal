<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LX\UserAuthController;
use App\Http\Controllers\LX\UserPwdResetController;
use App\Http\Controllers\LX\TrainController;


// =======================================================================
// 3.1 认证管理 /api/v1/user/auth
// =======================================================================

// 公开路由
Route::prefix('user/auth')->group(function () {
    Route::post('/login',         [UserAuthController::class, 'login']);
    Route::post('/refresh_token', [UserAuthController::class, 'refreshToken']);
});

// 需认证路由
Route::prefix('user/auth')->middleware('auth:user_api')->group(function () {
    Route::post('/logout',      [UserAuthController::class, 'logout']);
    Route::get('/info',         [UserAuthController::class, 'info']);
    Route::post('/update_info', [UserAuthController::class, 'updateInfo']);
    Route::post('/update_pwd',  [UserAuthController::class, 'updatePwd']);
});

// =======================================================================
// 3.2 忘记密码 /api/v1/user/pwd_reset
// =======================================================================

Route::prefix('user/pwd_reset')->group(function () {
    Route::post('/send_code', [UserPwdResetController::class, 'sendCode']);
    Route::post('/reset_pwd', [UserPwdResetController::class, 'resetPwd']);
});

// =======================================================================
// 3.3 用户端培训 /api/v1/user/train
// =======================================================================

// 需认证路由
Route::prefix('user/train')->middleware('auth:user_api')->group(function () {
    // 课程
    Route::prefix('course')->group(function () {
        Route::get('/list', [TrainController::class, 'courseList']);
        Route::post('/sign', [TrainController::class, 'courseSign']);
    });

    // 报名记录
    Route::prefix('sign')->group(function () {
        Route::get('/list', [TrainController::class, 'signList']);
    });

    // 作业
    Route::prefix('homework')->group(function () {
        Route::get('/list',   [TrainController::class, 'homeworkList']);
        Route::post('/submit', [TrainController::class, 'homeworkSubmit']);
    });
});
