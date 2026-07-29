<?php

namespace App\Traits;

use App\Services\DatabaseLogService;
use Illuminate\Support\Facades\Log;

/**
 * Service 层日志 Trait
 *
 * 同时写入文件日志（开发调试）和数据库日志表（生产审计）。
 *
 * 使用方式：在 Service 类中 use LogTrait;
 * 然后直接调用 $this->logBusiness(...) / $this->logException(...) 等
 */
trait LogTrait
{
    // ==================== 业务日志 ====================

    /**
     * 记录正常业务流程 → 文件 channel business + 数据库 sys_operation_log
     *
     * @param string $action  业务动作描述，如 "报名创建成功"、"审核通过"
     * @param array  $context 上下文数据
     */
    protected function logBusiness(string $action, array $context = []): void
    {
        // 文件日志
        Log::channel('business')->info($action, $context);

        // 数据库日志
        DatabaseLogService::logOperation(array_merge(
            $this->extractOperator($context),
            $this->extractTarget($context),
            ['action' => $action, 'module' => $this->inferModule($action, $context)],
        ));
    }

    /**
     * 记录重要操作（变更类），带 before/after 审计追溯 → 文件 channel business + 数据库 sys_operation_log
     *
     * @param string $action  操作描述，如 "管理员修改报名信息"
     * @param array  $before  变更前数据
     * @param array  $after   变更后数据
     * @param array  $extra   额外上下文
     */
    protected function logAudit(string $action, array $before = [], array $after = [], array $extra = []): void
    {
        // 文件日志
        Log::channel('business')->warning($action, array_merge([
            'before' => $before,
            'after'  => $after,
        ], $extra));

        // 数据库日志
        DatabaseLogService::logOperation(array_merge(
            $this->extractOperator($extra),
            $this->extractTarget($extra),
            [
                'action'      => $action,
                'module'      => $this->inferModule($action, $extra),
                'before_data' => $before,
                'after_data'  => $after,
            ],
        ));
    }

    // ==================== 异常日志 ====================

    /**
     * 记录异常 → 文件 channel exception + 数据库 sys_error_log
     *
     * @param string     $message 异常描述，如 "支付回调处理失败"
     * @param \Throwable $e       异常对象
     * @param array      $context 额外上下文（业务参数等）
     */
    protected function logException(string $message, \Throwable $e, array $context = []): void
    {
        // 文件日志
        Log::channel('exception')->error($message, array_merge([
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ], $context));

        // 数据库日志
        DatabaseLogService::logError([
            'level'             => 'error',
            'message'           => $message,
            'exception_message' => $e->getMessage(),
            'exception_file'    => $e->getFile(),
            'exception_line'    => $e->getLine(),
            'exception_trace'   => $e->getTraceAsString(),
            'channel'           => 'exception',
            'context'           => $context,
            ...$this->extractUserId($context),
        ]);
    }

    // ==================== 接口/第三方调用日志 ====================

    /**
     * 记录调用第三方接口（调用前）。
     *
     * @param string $apiName 接口名称，如 "微信支付-统一下单"
     * @param string $url     请求URL
     * @param array  $params  请求参数
     */
    protected function logApiRequest(string $apiName, string $url, array $params = []): void
    {
        Log::channel('api')->info("{$apiName} - 请求", [
            'url'     => $url,
            'request' => $params,
        ]);
    }

    /**
     * 记录第三方接口返回（调用后）。
     *
     * @param string $apiName  接口名称
     * @param array  $response 响应数据
     * @param int    $httpStatus HTTP 状态码
     */
    protected function logApiResponse(string $apiName, array $response, int $httpStatus = 200): void
    {
        $level = $httpStatus >= 500 ? 'error' : 'info';
        Log::channel('api')->{$level}("{$apiName} - 响应", [
            'response'    => $response,
            'http_status' => $httpStatus,
        ]);
    }

    // ==================== 登录日志 ====================

    /**
     * 记录登录事件 → 文件 channel business + 数据库 sys_login_log
     *
     * @param string      $loginType  登录类型，如 "用户登录"、"管理员登录"
     * @param int         $userId     用户/管理员ID
     * @param string      $username   用户名
     * @param int         $status     状态：1=成功 0=失败
     * @param string|null $failReason 失败原因（status=0时填写）
     */
    protected function logLogin(string $loginType, int $userId, string $username, int $status = 1, ?string $failReason = null): void
    {
        // 文件日志
        $level = $status === 1 ? 'info' : 'warning';
        Log::channel('business')->{$level}("{$loginType} - " . ($status === 1 ? '成功' : '失败'), [
            'user_id'  => $userId,
            'username' => $username,
            'status'   => $status,
            'reason'   => $failReason,
        ]);

        // 数据库日志
        DatabaseLogService::logLogin([
            'login_type'  => $loginType,
            'user_id'     => $userId,
            'username'    => $username,
            'status'      => $status,
            'fail_reason' => $failReason,
        ]);
    }

    // ==================== 性能日志 ====================

    /**
     * 检测 SQL 查询耗时，超过阈值记录慢查询。
     *
     * @param string $sqlDescription SQL 描述
     * @param float  $durationSec    查询耗时（秒）
     * @param float  $thresholdSec   告警阈值（秒），默认 1s
     */
    protected function logSlowQuery(string $sqlDescription, float $durationSec, float $thresholdSec = 1.0): void
    {
        if ($durationSec > $thresholdSec) {
            Log::channel('business')->warning('慢查询告警', [
                'sql'        => $sqlDescription,
                'duration_s' => round($durationSec, 3),
            ]);
        }
    }

    /**
     * 计时器：开始计时，返回 microtime。
     */
    protected function startTimer(): float
    {
        return microtime(true);
    }

    /**
     * 计时器：结束计时，返回耗时秒数。
     */
    protected function endTimer(float $startTime): float
    {
        return microtime(true) - $startTime;
    }

    // ==================== 私有辅助方法 ====================

    /**
     * 从 context 或当前 auth 中提取操作人信息
     */
    private function extractOperator(array $context): array
    {
        // 优先从 context 中取
        if (isset($context['admin_id'])) {
            return [
                'operator_type' => 'admin',
                'operator_id'   => $context['admin_id'],
                'operator_name' => $context['admin_name'] ?? null,
            ];
        }
        if (isset($context['user_id'])) {
            return [
                'operator_type' => 'user',
                'operator_id'   => $context['user_id'],
                'operator_name' => $context['username'] ?? $context['real_name'] ?? null,
            ];
        }

        // 尝试从当前认证用户获取
        try {
            if ($admin = auth('admin_api')->user()) {
                return [
                    'operator_type' => 'admin',
                    'operator_id'   => $admin->admin_id,
                    'operator_name' => $admin->admin_name ?? null,
                ];
            }
            if ($user = auth('user_api')->user()) {
                return [
                    'operator_type' => 'user',
                    'operator_id'   => $user->user_id,
                    'operator_name' => $user->username ?? null,
                ];
            }
        } catch (\Throwable $e) {
            // 忽略 JWT 解析异常
        }

        return [
            'operator_type' => null,
            'operator_id'   => null,
            'operator_name' => null,
        ];
    }

    /**
     * 从 context 中提取操作目标信息
     */
    private function extractTarget(array $context): array
    {
        $targetMap = [
            'course_id'     => 'course',
            'homework_id'   => 'homework',
            'submit_id'     => 'homework_submit',
            'sign_id'       => 'sign',
            'app_id'        => 'application',
            'training_id'   => 'training',
            'notice_id'     => 'notice',
            'config_key'    => 'system_config',
        ];

        foreach ($targetMap as $key => $type) {
            if (isset($context[$key])) {
                return [
                    'target_type' => $type,
                    'target_id'   => $context[$key],
                ];
            }
        }

        return [
            'target_type' => null,
            'target_id'   => null,
        ];
    }

    /**
     * 从 context 中提取 userId
     */
    private function extractUserId(array $context): array
    {
        if (isset($context['user_id'])) {
            return ['user_id' => $context['user_id']];
        }
        if (isset($context['admin_id'])) {
            return ['user_id' => $context['admin_id']];
        }
        return [];
    }

    /**
     * 从 action 字符串和 context 推断模块名称
     */
    private function inferModule(string $action, array $context): string
    {
        // 优先使用 context 中的 module
        if (isset($context['module'])) {
            return $context['module'];
        }

        // 从动作关键词推断
        $moduleMap = [
            '登录'   => '认证管理',
            '登出'   => '认证管理',
            '注册'   => '认证管理',
            '密码'   => '认证管理',
            '验证码' => '认证管理',
            '课程'   => '课程管理',
            '培训'   => '培训管理',
            '作业'   => '作业管理',
            '报名'   => '报名管理',
            '学员'   => '学员管理',
            '开关'   => '系统配置',
            '头像'   => '个人中心',
            '资料'   => '个人中心',
            '注销'   => '认证管理',
            '批改'   => '作业管理',
        ];

        foreach ($moduleMap as $keyword => $module) {
            if (mb_strpos($action, $keyword) !== false) {
                return $module;
            }
        }

        return '通用操作';
    }
}
