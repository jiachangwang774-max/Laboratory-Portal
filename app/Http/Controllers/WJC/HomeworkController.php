<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\HomeworkCreateRequest;
use App\Http\Requests\WJC\HomeworkUpdateRequest;
use App\Http\Requests\WJC\HomeworkListRequest;
use App\Http\Requests\WJC\HomeworkScoreRequest;
use App\Services\WJC\HomeworkService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function __construct(private HomeworkService $service) {}

    public function create(HomeworkCreateRequest $r): JsonResponse
    {
        $adminId = auth('admin_api')->user()->admin_id;
        return Result::success('作业布置成功', $this->service->create(
            $adminId, (int) $r->validated('courseId'),
            $r->validated('homeworkTitle'), $r->validated('homeworkContent'),
            $r->validated('deadline')
        ));
    }

    public function update(HomeworkUpdateRequest $r, int $homeworkId): JsonResponse
    {
        return Result::success('作业更新成功', $this->service->update($homeworkId, $r->validated()));
    }

    public function delete(int $homeworkId): JsonResponse
    {
        $this->service->delete($homeworkId);
        return Result::success('作业删除成功');
    }

    public function index(HomeworkListRequest $r): JsonResponse
    {
        return Result::success('成功', $this->service->list(
            (int) $r->input('page', 1), (int) $r->input('size', 10),
            $r->input('courseId') ? (int) $r->input('courseId') : null
        ));
    }

    // ===== 批改 =====

    public function submitList(HomeworkListRequest $r): JsonResponse
    {
        return Result::success('成功', $this->service->submitList(
            (int) $r->input('page', 1), (int) $r->input('size', 10),
            $r->input('homeworkId') ? (int) $r->input('homeworkId') : null,
            $r->input('courseId') ? (int) $r->input('courseId') : null
        ));
    }

    public function submitDetail(int $submitId): JsonResponse
    {
        return Result::success('成功', $this->service->submitDetail($submitId));
    }

    public function score(HomeworkScoreRequest $r): JsonResponse
    {
        $submitId = (int) request()->route('submitId');
        return Result::success('评分成功', $this->service->score(
            $submitId, (int) $r->validated('score'), $r->validated('remark')
        ));
    }

    public function deleteSubmit(Request $r): JsonResponse
    {
        $this->service->deleteSubmit((int) $r->input('submitId'));
        return Result::success('删除成功');
    }
}
