<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\CheckinCreateRequest;
use App\Http\Requests\WJC\CheckinListRequest;
use App\Services\WJC\CheckinService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    public function __construct(private CheckinService $service) {}

    /**
     * 发起签到
     * POST /admin/checkin/create
     */
    public function create(CheckinCreateRequest $r): JsonResponse
    {
        $adminId = auth('admin_api')->user()->admin_id;
        $data = $this->service->create($adminId, (int) $r->validated('courseId'), $r->input('sessionId') ? (int) $r->input('sessionId') : null, (int) $r->input('duration', 5));
        return Result::success('签到已发起', $data);
    }

    /**
     * 结束签到
     * PUT /admin/checkin/close
     */
    public function close(Request $r): JsonResponse
    {
        $this->service->close((int) $r->input('checkinId'));
        return Result::success('签到已结束');
    }

    /**
     * 签到列表
     * GET /admin/checkin/list
     */
    public function index(CheckinListRequest $r): JsonResponse
    {
        return Result::success('成功', $this->service->list(
            (int) $r->input('page', 1), (int) $r->input('size', 10),
            $r->input('courseId') ? (int) $r->input('courseId') : null,
            $r->input('sessionId') ? (int) $r->input('sessionId') : null
        ));
    }

    /**
     * 签到明细
     * GET /admin/checkin/records?checkinId=
     */
    public function records(Request $r): JsonResponse
    {
        return Result::success('成功', $this->service->records((int) $r->input('checkinId')));
    }

    /**
     * 手动签到
     * PUT /admin/checkin/manual
     */
    public function manual(Request $r): JsonResponse
    {
        return Result::success('手动签到成功', $this->service->manualCheckin(
            (int) $r->input('checkinId'), (int) $r->input('userId')
        ));
    }

    /**
     * 导出签到名单
     * GET /admin/checkin/export?checkinId=
     */
    public function export(Request $r): JsonResponse
    {
        $data = $this->service->export((int) $r->input('checkinId'));
        return Result::success('成功', ['list' => $data]);
    }

    public function delete(Request $r): JsonResponse
    {
        $this->service->delete((int) $r->input('checkinId'));
        return Result::success('删除成功');
    }
}
