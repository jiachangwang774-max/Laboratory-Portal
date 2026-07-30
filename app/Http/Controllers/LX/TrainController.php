<?php

namespace App\Http\Controllers\LX;

use App\Http\Controllers\Controller;
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
     * 获取课程详情（学生端）
     * GET /api/v1/user/train/course/detail/{id}
     */
    public function courseDetail(int $course_id): JsonResponse
    {
        $userId = auth('user_api')->user()->user_id;
        $data = $this->trainService->courseDetail($course_id, $userId);
        return Result::success('成功', $data);
    }

    /**
     * 获取培训详情
     * GET /api/v1/user/train/training/detail/{id}
     */
    public function trainingDetail(int $id): JsonResponse
    {
        $data = $this->trainService->trainingDetail($id);
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
