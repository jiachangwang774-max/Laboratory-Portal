<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LX\UserRegisterController;
use App\Http\Controllers\LX\UserAuthController;
use App\Http\Controllers\LX\UserPwdResetController;
use App\Http\Controllers\LX\TrainController;

// 公开路由
Route::prefix('user/auth')->group(function () {
    Route::post('/login',         [UserAuthController::class, 'login']);//登录
});

Route::prefix('user/register')->group(function () {
    Route::post('/send_code', [UserRegisterController::class, 'sendCode']);//发送验证码（注册/注销）
    Route::post('/',          [UserRegisterController::class, 'register']);//用户注册
});

// 需认证路由 - 注销账号
Route::prefix('user/register')->middleware('auth:user_api')->group(function () {
    Route::post('/delete_account', [UserRegisterController::class, 'deleteAccount']);//注销账号
});

// =======================================================================
// 3.3 忘记密码 /api/v1/user/pwd_reset
// =======================================================================
Route::prefix('user/pwd_reset')->group(function () {
    Route::post('/send_code', [UserPwdResetController::class, 'sendCode']);//发送重置密码验证码
    Route::post('/reset_pwd', [UserPwdResetController::class, 'resetPwd']);//重置密码
});


// 需认证路由
Route::prefix('user/auth')->middleware('auth:user_api')->group(function () {
    Route::post('/logout',      [UserAuthController::class, 'logout']);//退出登录
    Route::get('/info',         [UserAuthController::class, 'info']);//获取用户信息
    Route::post('/update_info', [UserAuthController::class, 'updateInfo']);//更新用户信息
    Route::post('/update_pwd',  [UserAuthController::class, 'updatePwd']);//更新用户密码
});


// =======================================================================
// 3.4 用户端培训 /api/v1/user/train
// =======================================================================

// 需认证路由
Route::prefix('user/train')->middleware('auth:user_api')->group(function () {
    Route::prefix('course')->group(function () {
        Route::get('/list', [TrainController::class, 'courseList']);//获取课程列表
        Route::post('/sign', [TrainController::class, 'courseSign']);//报名课程
    });

    // 报名记录
    Route::prefix('sign')->group(function () {
        Route::get('/list', [TrainController::class, 'signList']);//获取报名记录列表
    });

    // 作业
    Route::prefix('homework')->group(function () {
        Route::get('/list',   [TrainController::class, 'homeworkList']);//获取作业列表
        Route::post('/submit', [TrainController::class, 'homeworkSubmit']);//提交作业
    });

});
