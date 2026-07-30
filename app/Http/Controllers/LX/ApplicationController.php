<?php

namespace App\Http\Controllers\LX;

use App\Http\Controllers\Controller;
use App\Http\Requests\LX\SaveDraftRequest;
use App\Http\Requests\LX\SubmitApplicationRequest;
use App\Services\LX\ApplicationService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationService $applicationService
    ) {}

    /**
     * 保存报名草稿
     * POST /api/v1/user/application/draft/save
     */
    public function saveDraft(SaveDraftRequest $request): JsonResponse
    {
        $data   = $request->validated();
        $result = $this->applicationService->saveDraft($data);
        return Result::success('草稿已保存', $result);
    }

    /**
     * 提交报名申请
     * POST /api/v1/user/application/submit
     */
    public function submit(SubmitApplicationRequest $request): JsonResponse
    {
        $data   = $request->validated();
        $result = $this->applicationService->submit($data);
        return Result::success('报名提交成功', $result);
    }

    /**
     * 获取报名草稿
     * GET /api/v1/user/application/draft?student_id=xxx
     */
    public function draft(Request $request): JsonResponse
    {
        $studentId = $request->input('student_id');
        if (empty($studentId)) {
            return Result::error(\App\Enums\ResponseCode::PARAM_ERROR, '学号不能为空');
        }
        $result = $this->applicationService->getDraft($studentId);
        return Result::success('获取成功', $result);
    }

    /**
     * 获取已提交的报名详情及审核结果（需登录）
     * GET /api/v1/user/application/detail
     */
    public function detail(): JsonResponse
    {
        $studentId = auth('user_api')->user()->student_id;
        $result = $this->applicationService->getDetail($studentId);
        return Result::success('获取成功', $result);
    }
}
