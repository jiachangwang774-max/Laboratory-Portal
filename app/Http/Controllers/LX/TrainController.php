<?php

namespace App\Http\Controllers\LX;

use App\Http\Controllers\Controller;
use App\Http\Requests\LX\CourseListRequest;
use App\Http\Requests\LX\CourseSignRequest;
use App\Http\Requests\LX\SignListRequest;
use App\Http\Requests\LX\HomeworkListRequest;
use App\Http\Requests\LX\HomeworkSubmitRequest;
use App\Services\LX\TrainService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class TrainController extends Controller
{
    public function __construct(
        private TrainService $trainService
    ) {}

    /**
     * 获取培训课程分页列表
     * GET /api/v1/user/train/course/list
     */
    public function courseList(CourseListRequest $request): JsonResponse
    {
        $data = $this->trainService->courseList(
            (int) $request->input('page', 1),
            (int) $request->input('size', 10)
        );
        return Result::success('成功', $data);
    }

    /**
     * 报名培训课程
     * POST /api/v1/user/train/course/sign
     */
    public function courseSign(CourseSignRequest $request): JsonResponse
    {
        $userId = auth('user_api')->user()->user_id;
        $data = $this->trainService->courseSign(
            $userId,
            (int) $request->validated('courseId'),
            $request->validated('signInfo')
        );
        return Result::success('报名提交成功，等待管理员审核', $data);
    }

    /**
     * 我的报名记录分页
     * GET /api/v1/user/train/sign/list
     */
    public function signList(SignListRequest $request): JsonResponse
    {
        $userId = auth('user_api')->user()->user_id;
        $data = $this->trainService->signList(
            $userId,
            (int) $request->input('page', 1),
            (int) $request->input('size', 10)
        );
        return Result::success('成功', $data);
    }

    /**
     * 我的作业列表
     * GET /api/v1/user/train/homework/list
     */
    public function homeworkList(HomeworkListRequest $request): JsonResponse
    {
        $userId = auth('user_api')->user()->user_id;
        $data = $this->trainService->homeworkList(
            $userId,
            (int) $request->input('page', 1),
            (int) $request->input('size', 10),
            $request->input('courseId') ? (int) $request->input('courseId') : null
        );
        return Result::success('成功', $data);
    }

    /**
     * 提交作业
     * POST /api/v1/user/train/homework/submit
     */
    public function homeworkSubmit(HomeworkSubmitRequest $request): JsonResponse
    {
        $userId = auth('user_api')->user()->user_id;
        $data = $this->trainService->homeworkSubmit(
            $userId,
            (int) $request->validated('homeworkId'),
            $request->validated('content'),
            $request->file('file')
        );
        return Result::success('作业提交成功', $data);
    }
}
