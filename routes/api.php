<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LX\UserRegisterController;
use App\Http\Controllers\LX\UserAuthController;
use App\Http\Controllers\LX\UserPwdResetController;
use App\Http\Controllers\LX\TrainController;
use App\Http\Controllers\WJC\AdminAuthController;
use App\Http\Controllers\WJC\CourseController;
use App\Http\Controllers\WJC\HomeworkController;
use App\Http\Controllers\WJC\PerformanceController;
use App\Http\Controllers\WJC\SignAuditController;
use App\Http\Controllers\WJC\SignSwitchController;
use App\Http\Controllers\WJC\UserManageController;
use App\Http\Controllers\LX\ApplicationController;

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
    Route::post('/upload_avatar', [UserAuthController::class, 'uploadAvatar']);//上传头像
});

// 3.4 用户端培训 /api/v1/user/train
Route::prefix('user/train')->middleware('auth:user_api')->group(function () {
    Route::prefix('course')->group(function () {
        Route::get('/detail/{course_id}', [TrainController::class, 'courseDetail'])->whereNumber('course_id');//获取课程详情
    });

    Route::prefix('training')->group(function () {
        Route::get('/detail/{id}', [TrainController::class, 'trainingDetail'])->whereNumber('id');//获取培训详情
    });

    // 作业
    Route::prefix('homework')->group(function () {
        Route::get('/list',   [TrainController::class, 'homeworkList']);//获取作业列表
        Route::post('/submit', [TrainController::class, 'homeworkSubmit']);//提交作业
    });
});

// 3.5 报名申请表 /api/v1/user/application（无需登录）
Route::prefix('user/application')->group(function () {
    // 草稿
    Route::prefix('draft')->group(function () {
        Route::post('/save', [ApplicationController::class, 'saveDraft']);//保存草稿
        Route::get('/',      [ApplicationController::class, 'draft']);//获取草稿
    });

    // 提交与详情
    Route::post('/submit', [ApplicationController::class, 'submit']);//提交报名
    Route::get('/detail',  [ApplicationController::class, 'detail']);//获取已提交详情
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

// 4.2 报名开关 /api/v1/admin/sign_switch — 公开路由
Route::prefix('admin/sign_switch')->group(function () {
    Route::get('/front/get', [SignSwitchController::class, 'frontGet']);//前台查询报名开关
});

// 4.2 报名开关 — 需鉴权路由
Route::prefix('admin/sign_switch')->middleware('auth:admin_api')->group(function () {
    Route::get('/get',    [SignSwitchController::class, 'get']);//查询报名开关
    Route::put('/update', [SignSwitchController::class, 'update']);//修改报名开关
});

// 4.5 报名管理 /api/v1/admin/sign — 需鉴权路由
Route::prefix('admin/sign')->middleware('auth:admin_api')->group(function () {
    Route::get('/list',             [SignAuditController::class, 'index']);//报名列表
    Route::get('/detail/{signId}',  [SignAuditController::class, 'detail'])->whereNumber('signId');//报名详情
    Route::put('/cancel/{signId}',  [SignAuditController::class, 'cancel'])->whereNumber('signId');//取消报名
    Route::get('/export',           [SignAuditController::class, 'export']);//导出报名表
});

// 4.4 课程管理 — 需鉴权
Route::prefix('admin/course')->middleware('auth:admin_api')->group(function () {
    Route::post('/create',           [CourseController::class, 'create']);//创建课程
    Route::put('/update/{courseId}', [CourseController::class, 'update'])->whereNumber('courseId');//编辑
    Route::delete('/delete/{courseId}', [CourseController::class, 'delete'])->whereNumber('courseId');//删除
    Route::get('/list',              [CourseController::class, 'index']);//列表
    Route::get('/detail/{courseId}', [CourseController::class, 'detail'])->whereNumber('courseId');//详情
    Route::put('/status/{courseId}', [CourseController::class, 'status'])->whereNumber('courseId');//上下架
});

// 4.5 作业布置 — 需鉴权
Route::prefix('admin/homework')->middleware('auth:admin_api')->group(function () {
    Route::post('/create',              [HomeworkController::class, 'create']);//布置作业
    Route::put('/update/{homeworkId}',  [HomeworkController::class, 'update'])->whereNumber('homeworkId');//编辑
    Route::delete('/delete/{homeworkId}', [HomeworkController::class, 'delete'])->whereNumber('homeworkId');//删除
    Route::get('/list',                 [HomeworkController::class, 'index']);//列表
});

// 4.6 作业批改 — 需鉴权
Route::prefix('admin/homework/submit')->middleware('auth:admin_api')->group(function () {
    Route::get('/list',               [HomeworkController::class, 'submitList']);//提交列表
    Route::get('/detail/{submitId}',  [HomeworkController::class, 'submitDetail'])->whereNumber('submitId');//详情
    Route::put('/score/{submitId}',   [HomeworkController::class, 'score'])->whereNumber('submitId');//评分
});

// 4.7 学员账号管理 — 需鉴权
Route::prefix('admin/user')->middleware('auth:admin_api')->group(function () {
    Route::get('/list',            [UserManageController::class, 'index']);//列表
    Route::get('/detail/{userId}', [UserManageController::class, 'detail'])->whereNumber('userId');//详情
    Route::put('/status/{userId}', [UserManageController::class, 'status'])->whereNumber('userId');//启用禁用
    Route::post('/create',         [UserManageController::class, 'create']);//创建
});

// 4.8 学员表现 — 需鉴权
Route::prefix('admin/performance')->middleware('auth:admin_api')->group(function () {
    Route::get('/list',             [PerformanceController::class, 'index']);//汇总
    Route::get('/detail/{userId}',  [PerformanceController::class, 'detail'])->whereNumber('userId');//详情
});
