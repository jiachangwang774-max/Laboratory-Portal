<?php

namespace App\Http\Controllers\LX;

use App\Http\Controllers\Controller;
use App\Http\Requests\LX\HomeworkListRequest;
use App\Http\Requests\LX\HomeworkSubmitRequest;
use App\Services\LX\TrainService;
use App\Services\WJC\CheckinService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainController extends Controller
{
    public function __construct(
        private TrainService $trainService,
        private CheckinService $checkinService
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
     * 获取我的课程详情（学生端）
     * GET /api/v1/user/train/course/my-course
     *
     * 获取当前登录学生通过报名审核后随机分配的课程详情及班级号
     */
    public function myCourse(): JsonResponse
    {
        $userId = auth('user_api')->user()->user_id;
        $data = $this->trainService->myCourse($userId);
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

    /**
     * 获取单个作业详情
     * GET /api/v1/user/train/homework/detail/{homework_id}
     */
    public function homeworkDetail(int $homework_id): JsonResponse
    {
        $userId = auth('user_api')->user()->user_id;
        $data = $this->trainService->homeworkDetail($homework_id, $userId);
        return Result::success('成功', $data);
    }

    /**
     * 待完成作业列表
     * GET /api/v1/user/train/homework/pending
     */
    public function homeworkPending(): JsonResponse
    {
        $userId = auth('user_api')->user()->user_id;
        $data = $this->trainService->homeworkPending($userId);
        return Result::success('成功', $data);
    }

    /**
     * 待批阅作业列表
     * GET /api/v1/user/train/homework/submitted
     */
    public function homeworkSubmitted(): JsonResponse
    {
        $userId = auth('user_api')->user()->user_id;
        $data = $this->trainService->homeworkSubmitted($userId);
        return Result::success('成功', $data);
    }

    /**
     * 已批阅作业列表
     * GET /api/v1/user/train/homework/scored
     */
    public function homeworkScored(): JsonResponse
    {
        $userId = auth('user_api')->user()->user_id;
        $data = $this->trainService->homeworkScored($userId);
        return Result::success('成功', $data);
    }

    /**
     * 学生签到（通过课程ID + 签到码）
     * POST /api/v1/user/train/checkin
     *
     * 传入 courseId 和 code，校验该课程下是否存在有效的签到码，
     * 同时校验学生是否已报名该课程
     */
    public function checkin(Request $request): JsonResponse
    {
        $userId   = auth('user_api')->user()->user_id;
        $courseId = $request->input('courseId');
        $code     = $request->input('code');

        if (!$courseId || !is_numeric($courseId)) {
            return Result::error(\App\Enums\ResponseCode::PARAM_ERROR, '课程ID不能为空');
        }

        if (!$code || strlen($code) !== 6 || !ctype_digit($code)) {
            return Result::error(\App\Enums\ResponseCode::PARAM_ERROR, '请输入有效的6位签到码');
        }

        $data = $this->checkinService->studentCheckinByCode($userId, (int) $courseId, $code);
        return Result::success('签到成功', $data);
    }

    /**
     * 课堂出勤率
     * GET /api/v1/user/train/performance/attendance?groupName=
     *
     * 出勤率 = 学生在该班级所有课程中的已签到次数 / 班级总签到次数
     */
    public function attendanceRate(Request $request): JsonResponse
    {
        $userId    = auth('user_api')->user()->user_id;
        $groupName = $request->input('groupName');

        if (!$groupName) {
            return Result::error(\App\Enums\ResponseCode::PARAM_ERROR, '班级名称不能为空');
        }

        $data = $this->trainService->attendanceRate($userId, $groupName);
        return Result::success('成功', $data);
    }

    /**
     * 作业完成率
     * GET /api/v1/user/train/performance/homework-rate?groupName=
     *
     * 完成率 = 学生在该班级所有课程中的已提交作业数 / 班级总作业数
     */
    public function homeworkRate(Request $request): JsonResponse
    {
        $userId    = auth('user_api')->user()->user_id;
        $groupName = $request->input('groupName');

        if (!$groupName) {
            return Result::error(\App\Enums\ResponseCode::PARAM_ERROR, '班级名称不能为空');
        }

        $data = $this->trainService->homeworkRate($userId, $groupName);
        return Result::success('成功', $data);
    }

    /**
     * 平均成绩
     * GET /api/v1/user/train/performance/avg-score?groupName=
     *
     * 平均成绩 = 学生在该班级所有课程中已批阅作业分数的平均值
     */
    public function avgScore(Request $request): JsonResponse
    {
        $userId    = auth('user_api')->user()->user_id;
        $groupName = $request->input('groupName');

        if (!$groupName) {
            return Result::error(\App\Enums\ResponseCode::PARAM_ERROR, '班级名称不能为空');
        }

        $data = $this->trainService->avgScore($userId, $groupName);
        return Result::success('成功', $data);
    }
}
