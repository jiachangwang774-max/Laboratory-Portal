<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\CourseCreateRequest;
use App\Http\Requests\WJC\CourseUpdateRequest;
use App\Http\Requests\WJC\CourseListRequest;
use App\Http\Requests\WJC\CourseStatusRequest;
use App\Services\WJC\CourseService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    public function __construct(private CourseService $service) {}

    public function create(CourseCreateRequest $r): JsonResponse
    {
        $adminId = auth('admin_api')->user()->admin_id;
        return Result::success('课程创建成功', $this->service->create($adminId, $r->validated()));
    }

    public function update(CourseUpdateRequest $r, int $courseId): JsonResponse
    {
        return Result::success('课程更新成功', $this->service->update($courseId, $r->validated()));
    }

    public function delete(int $courseId): JsonResponse
    {
        $this->service->delete($courseId);
        return Result::success('课程删除成功');
    }

    public function index(CourseListRequest $r): JsonResponse
    {
        $data = $this->service->list(
            (int) $r->input('page', 1), (int) $r->input('size', 10),
            $r->input('courseName'), $r->input('status') !== null ? (int) $r->input('status') : null
        );
        return Result::success('成功', $data);
    }

    public function detail(int $courseId): JsonResponse
    {
        return Result::success('成功', $this->service->detail($courseId));
    }

    public function status(CourseStatusRequest $r, int $courseId): JsonResponse
    {
        return Result::success('课程状态已更新', $this->service->status($courseId, (int) $r->validated('status')));
    }
}
