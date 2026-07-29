<?php

namespace App\Http\Controllers\WJC;

use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\PerformanceListRequest;
use App\Services\WJC\PerformanceService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

class PerformanceController extends Controller
{
    public function __construct(private PerformanceService $service) {}

    public function index(PerformanceListRequest $r): JsonResponse
    {
        return Result::success('成功', $this->service->list(
            (int) $r->input('page', 1), (int) $r->input('size', 10),
            $r->input('courseId') ? (int) $r->input('courseId') : null,
            $r->input('keyword')
        ));
    }

    public function detail(int $userId): JsonResponse
    {
        $courseId = request()->input('courseId') ? (int) request()->input('courseId') : null;
        return Result::success('成功', $this->service->detail($userId, $courseId));
    }
}
