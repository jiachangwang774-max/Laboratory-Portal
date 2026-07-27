<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Service 层日志 Trait
 *
 * 使用方式：在 Service 类中 use LogTrait;
 * 然后直接调用 $this->logBusiness(...) / $this->logException(...) 等
 */
trait LogTrait
{
    // ==================== 业务日志 ====================

    /**
     * 记录正常业务流程。
     *
     * @param string $action  业务动作描述，如 "报名创建成功"、"审核通过"
     * @param array  $context 上下文数据
     */
    protected function logBusiness(string $action, array $context = []): void
    {
        Log::channel('business')->info($action, $context);
    }

    /**
     * 记录重要操作（变更类），带 before/after 审计追溯。
     *
     * @param string $action  操作描述，如 "管理员修改报名信息"
     * @param array  $before  变更前数据
     * @param array  $after   变更后数据
     * @param array  $extra   额外上下文
     */
    protected function logAudit(string $action, array $before = [], array $after = [], array $extra = []): void
    {
        Log::channel('business')->warning($action, array_merge([
            'before' => $before,
            'after'  => $after,
        ], $extra));
    }

    // ==================== 异常日志 ====================

    /**
     * 记录异常（在 catch 块中调用）。
     *
     * @param string     $message 异常描述，如 "支付回调处理失败"
     * @param \Throwable $e       异常对象
     * @param array      $context 额外上下文（业务参数等）
     */
    protected function logException(string $message, \Throwable $e, array $context = []): void
    {
        Log::channel('exception')->error($message, array_merge([
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ], $context));
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
}
