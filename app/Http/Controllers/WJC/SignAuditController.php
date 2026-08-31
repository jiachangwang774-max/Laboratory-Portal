<?php

namespace App\Http\Controllers\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\WJC\ImportClassRequest;
use App\Http\Requests\WJC\SignAuditRequest;
use App\Http\Requests\WJC\SignListRequest;
use App\Services\WJC\SignAuditService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OSS\Core\OssException;
use OSS\Http\RequestCore_Exception;
use OSS\OssClient;

class SignAuditController extends Controller
{
    public function __construct(
        private SignAuditService $signService
    ) {}

    /**
     * 报名申请列表
     * GET /api/v1/admin/sign/list
     */
    public function index(SignListRequest $request): JsonResponse
    {
        $data = $this->signService->list(
            (int) $request->input('page', 1),
            (int) $request->input('size', 10),
            $request->input('auditStatus') !== null ? (int) $request->input('auditStatus') : null,
            $request->input('college') ?: null,
            $request->input('major') ?: null
        );
        return Result::success('成功', $data);
    }

    /**
     * 报名申请详情
     * GET /api/v1/admin/sign/detail/{id}
     */
    public function detail(int $id): JsonResponse
    {
        $data = $this->signService->detail($id);
        return Result::success('成功', $data);
    }

    /**
     * 审核通过
     * PUT /api/v1/admin/sign/approve/{id}
     */
    public function approve(int $id): JsonResponse
    {
        $data = $this->signService->approve($id);
        return Result::success('审核通过', $data);
    }

    /**
     * 审核驳回
     * PUT /api/v1/admin/sign/reject/{id}
     */
    public function reject(SignAuditRequest $request, int $id): JsonResponse
    {
        $data = $this->signService->reject($id, $request->validated('remark'));
        return Result::success('已驳回', $data);
    }

    /**
     * 重新分班
     * POST /api/v1/admin/sign/regroup
     */
    public function regroup(): JsonResponse
    {
        $data = $this->signService->regroup();
        return Result::success('分班完成', $data);
    }

    /**
     * 培训名单 - 按班级查看学生
     * GET /admin/sign/class/list?groupName=一班
     */
    public function classList(Request $r): JsonResponse
    {
        $groups = [1 => '一班', 2 => '二班', 3 => '三班'];
        $id = (int) $r->input('groupId', 1);
        $data = $this->signService->classList($groups[$id] ?? '一班');
        return Result::success('成功', $data);
    }

    /**
     * 培训名单 - 导出
     * GET /admin/sign/class/export?groupId=1
     */
    public function classExport(Request $r): JsonResponse
    {
        $groups = [1 => '一班', 2 => '二班', 3 => '三班'];
        $id = (int) $r->input('groupId', 1);
        $data = $this->signService->classExport($groups[$id] ?? '一班');
        return Result::success('成功', ['list' => $data]);
    }

    /**
     * 导入分班Excel
     * POST /admin/sign/import
     *
     * 文件上传至 OSS class-imports/ 目录归档，同时本地临时解析
     */
    public function importClass(ImportClassRequest $r): JsonResponse
    {
        $file = $r->file('file');
        $ext  = $file->getClientOriginalExtension();
        $object = 'class-imports/' . uniqid() . '.' . $ext;

        // 上传至 OSS class-imports/ 目录
        try {
            $oss = new OssClient(
                config('filesystems.disks.oss.access_id'),
                config('filesystems.disks.oss.access_key'),
                config('filesystems.disks.oss.endpoint'),
            );
            $oss->putObject(config('filesystems.disks.oss.bucket'), $object, $file->getContent());
        } catch (OssException | RequestCore_Exception $e) {
            \Illuminate\Support\Facades\Log::channel('exception')->error('OSS上传分班Excel失败', [
                'object'  => $object,
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);
            throw new BusinessException('文件上传失败，请稍后重试', ResponseCode::THIRD_PARTY_ERROR);
        }

        $ossUrl = 'https://' . config('filesystems.disks.oss.bucket') . '.'
                . config('filesystems.disks.oss.endpoint') . '/' . $object;

        // 本地临时文件用于 ZipArchive 解析
        $path = $file->storeAs('temp', uniqid() . '.' . $ext);
        $data = $this->signService->importClass(storage_path('app/' . $path));
        \Illuminate\Support\Facades\Storage::delete($path);

        $data['ossUrl'] = $ossUrl;
        return Result::success("导入完成，成功 {$data['successCount']} 条，失败 {$data['failCount']} 条", $data);
    }
}
