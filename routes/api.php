<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LX\UserRegisterController;
use App\Http\Controllers\LX\UserAuthController;
use App\Http\Controllers\LX\UserPwdResetController;
use App\Http\Controllers\LX\TrainController;
use App\Http\Controllers\WJC\AdminAuthController;
use App\Http\Controllers\WJC\NoticeController;
use App\Http\Controllers\WJC\SignAuditController;
use App\Http\Controllers\WJC\SignSwitchController;

// =======================================================================
// 3. 前台用户端接口 /api/v1/user
// =======================================================================

// 公开路由
Route::prefix('user/auth')->group(function () {
    Route::post('/login',         [UserAuthController::class, 'login']);//登录
});

// 统一发送验证码（注册 / 重置密码 / 注销账号）
Route::post('user/verify_code/send', [UserRegisterController::class, 'sendCode']);

Route::prefix('user/register')->group(function () {
    Route::post('/', [UserRegisterController::class, 'register']);//用户注册
});

// 需认证路由 - 注销账号
Route::prefix('user/register')->middleware('auth:user_api')->group(function () {
    Route::post('/delete_account', [UserRegisterController::class, 'deleteAccount']);//注销账号
});

// 3.3 忘记密码 /api/v1/user/pwd_reset
Route::prefix('user/pwd_reset')->group(function () {
    Route::post('/reset_pwd', [UserPwdResetController::class, 'resetPwd']);//重置密码
});

// 需认证路由
Route::prefix('user/auth')->middleware('auth:user_api')->group(function () {
    Route::post('/logout',      [UserAuthController::class, 'logout']);//退出登录
    Route::get('/info',         [UserAuthController::class, 'info']);//获取用户信息
    Route::post('/update_info', [UserAuthController::class, 'updateInfo']);//更新用户信息
    Route::post('/update_pwd',  [UserAuthController::class, 'updatePwd']);//更新用户密码
});

// 3.4 用户端培训 /api/v1/user/train
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

// =======================================================================
// 4. 管理员后台接口 /api/v1/admin
// =======================================================================

// 4.1 管理员认证 — 公开路由
Route::prefix('admin/auth')->group(function () {
    Route::post('/login',     [AdminAuthController::class, 'login']);//管理员登录
    Route::post('/send_code', [AdminAuthController::class, 'sendCode']);//发送验证码（找回密码）
    Route::post('/reset_pwd', [AdminAuthController::class, 'resetPwd']);//重置密码
});

// 4.1 管理员认证 — 需鉴权路由
Route::prefix('admin/auth')->middleware('auth:admin_api')->group(function () {
    Route::post('/logout',     [AdminAuthController::class, 'logout']);//退出登录
    Route::get('/info',        [AdminAuthController::class, 'info']);//获取管理员信息
    Route::post('/update_pwd', [AdminAuthController::class, 'updatePwd']);//修改密码
});

// 4.2 新闻公告 — 公开路由
Route::prefix('admin/notice')->group(function () {
    Route::get('/front/list',        [NoticeController::class, 'frontList']);//前台获取公告列表
    Route::get('/front/detail/{id}', [NoticeController::class, 'frontDetail'])->whereNumber('id');//前台获取公告详情
});

// 4.2 新闻公告 — 需鉴权路由
Route::prefix('admin/notice')->middleware('auth:admin_api')->group(function () {
    Route::post('/create',         [NoticeController::class, 'create']);//创建公告
    Route::delete('/delete/{id}',  [NoticeController::class, 'delete'])->whereNumber('id');//删除公告
    Route::put('/update/{id}',     [NoticeController::class, 'update'])->whereNumber('id');//更新公告
    Route::get('/list',            [NoticeController::class, 'list']);//后台获取公告列表
    Route::get('/detail/{id}',     [NoticeController::class, 'detail'])->whereNumber('id');//后台获取公告详情
});

// 4.3 报名开关 /api/v1/admin/sign_switch — 公开路由
Route::prefix('admin/sign_switch')->group(function () {
    Route::get('/front/get', [SignSwitchController::class, 'frontGet']);//前台查询报名开关
});

// 4.3 报名开关 — 需鉴权路由
Route::prefix('admin/sign_switch')->middleware('auth:admin_api')->group(function () {
    Route::get('/get',    [SignSwitchController::class, 'get']);//查询报名开关
    Route::put('/update', [SignSwitchController::class, 'update']);//修改报名开关
});

// 4.4 报名审核 /api/v1/admin/sign_audit — 需鉴权路由
Route::prefix('admin/sign_audit')->middleware('auth:admin_api')->group(function () {
    Route::get('/list',                  [SignAuditController::class, 'list']);//获取报名列表
    Route::get('/detail/{signId}',       [SignAuditController::class, 'detail'])->whereNumber('signId');//获取报名详情
    Route::put('/single_audit/{signId}', [SignAuditController::class, 'singleAudit'])->whereNumber('signId');//单条审核
    Route::post('/batch_audit',          [SignAuditController::class, 'batchAudit']);//批量审核
});
