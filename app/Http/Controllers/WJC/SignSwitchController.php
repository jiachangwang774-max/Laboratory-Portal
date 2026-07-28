<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\SignSwitchUpdateRequest;
use App\Services\WJC\SignSwitchService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class SignSwitchController extends Controller
{
    public function __construct(
        private SignSwitchService $signSwitchService
    ) {}

    /**
     * 查询报名开关
     * GET /api/v1/admin/sign_switch/get
     */
    public function get(): JsonResponse
    {
        $data = $this->signSwitchService->get();
        return Result::success('成功', $data);
    }

    /**
     * 修改报名开关
     * PUT /api/v1/admin/sign_switch/update
     */
    public function update(SignSwitchUpdateRequest $request): JsonResponse
    {
        $data = $this->signSwitchService->update((int) $request->validated('value'));
        return Result::success('报名状态修改成功', $data);
    }

    /**
     * 前台查询报名开关
     * GET /api/v1/admin/sign_switch/front/get
     */
    public function frontGet(): JsonResponse
    {
        $data = $this->signSwitchService->frontGet();
        return Result::success('成功', $data);
    }
}
