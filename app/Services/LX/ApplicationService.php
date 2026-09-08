<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\SignApplication;
use App\Models\SignQuota;
use App\Traits\LogTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ApplicationService
{
    use LogTrait;

    /**
     * 报名人数上限默认值（sign_quota 行缺失时兜底）
     */
    private const DEFAULT_QUOTA = 300;

    /**
     * 保存草稿（允许部分字段为空）
     *
     * 以学号为标识，每人仅有一份报名记录，若已存在则更新
     */
    public function saveDraft(array $data): array
    {
        $studentId = $data['student_id'];
        $application = SignApplication::where('student_id', $studentId)->first();

        if ($application) {
            // 已提交的报名不可再修改
            if ($application->status === 1) {
                throw new BusinessException('报名已提交，无法修改', ResponseCode::BUSINESS_ERROR);
            }

            $application = $this->updateDraft($application, $data);

            $this->logBusiness('报名草稿更新', [
                'student_id' => $studentId,
                'app_id'     => $application->id,
            ]);
        } else {
            $application = $this->createDraft($studentId, $data);

            $this->logBusiness('报名草稿创建', [
                'student_id' => $studentId,
                'app_id'     => $application->id,
            ]);
        }

        return $this->formatApplication($application);
    }

    /**
     * 提交报名（所有字段必填，由 FormRequest 保证）
     *
     * 用事务 + 名额单行行锁串行化所有提交，保证「300 人上限」与「防重复」在并发下原子生效。
     */
    public function submit(array $data): array
    {
        $studentId = $data['student_id'];

        $application = DB::transaction(function () use ($data, $studentId) {
            // 1. 锁定名额单行：串行化所有报名提交，防止并发超卖
            $quota = SignQuota::query()->lockForUpdate()->find(1);

            // 2. 防重复提交（同一学号已提交则幂等拒绝）
            $application = SignApplication::where('student_id', $studentId)->first();
            if ($application && $application->status === 1) {
                throw new BusinessException('报名已提交，请勿重复操作', ResponseCode::DUPLICATE_SUBMIT);
            }

            // 3. 名额校验（走到这里必然是新增提交或草稿转正式，都会占名额）
            $limit = $quota?->limit_count ?? self::DEFAULT_QUOTA;
            if (SignApplication::where('status', 1)->count() >= $limit) {
                throw new BusinessException('报名人数已满，请关注后续补录通知', ResponseCode::SIGN_QUOTA_FULL);
            }

            $fillData = [
                'name'              => $data['name'],
                'student_id'        => $studentId,
                'department'        => $data['department'],
                'college'           => $data['college'],
                'major'             => $data['major'],
                'class_name'        => $data['class_name'],
                'phone'             => $data['phone'],
                'self_introduction' => $data['self_introduction'],
                'status'            => 1,
                'submit_time'       => now(),
            ];

            if ($application) {
                $application->fill($fillData)->save();
            } else {
                try {
                    $application = SignApplication::create($fillData);
                } catch (QueryException $e) {
                    if (!$this->isDuplicateEntry($e)) {
                        throw $e;
                    }

                    // 并发下被「保存草稿」抢先创建，回读后升级为已提交
                    $application = SignApplication::where('student_id', $studentId)->first();
                    if (!$application) {
                        throw $e;
                    }
                    if ($application->status === 1) {
                        throw new BusinessException('报名已提交，请勿重复操作', ResponseCode::DUPLICATE_SUBMIT);
                    }
                    $application->fill($fillData)->save();
                }
            }

            return $application;
        });

        $this->logBusiness('报名提交成功', [
            'student_id' => $studentId,
            'app_id'     => $application->id,
            'department' => $data['department'],
        ]);

        return $this->formatApplication($application);
    }

    /**
     * 查询报名名额（公开，供前端展示剩余名额）
     */
    public function getQuota(): array
    {
        $limit = SignQuota::find(1)?->limit_count ?? self::DEFAULT_QUOTA;
        $used  = SignApplication::where('status', 1)->count();

        return [
            'limit'  => $limit,
            'used'   => $used,
            'remain' => max(0, $limit - $used),
            'full'   => $used >= $limit,
        ];
    }

    /**
     * 获取草稿
     */
    public function getDraft(string $studentId): ?array
    {
        $application = SignApplication::where('student_id', $studentId)
            ->where('status', 0)
            ->first();

        if (!$application) {
            return null;
        }

        return $this->formatApplication($application);
    }

    /**
     * 获取已提交的报名详情
     */
    public function getDetail(string $studentId): ?array
    {
        $application = SignApplication::where('student_id', $studentId)
            ->where('status', 1)
            ->first();

        if (!$application) {
            return null;
        }

        return $this->formatApplication($application);
    }

    /**
     * 创建草稿记录（并发下可能撞学号唯一索引，回读后按更新处理）
     */
    private function createDraft(string $studentId, array $data): SignApplication
    {
        try {
            return SignApplication::create([
                'student_id' => $studentId,
                'status'     => 0,
                'lab_id'     => ($data['department'] ?? 1) == 2 ? 'ai' : 'software',
                ...$data,
            ]);
        } catch (QueryException $e) {
            if (!$this->isDuplicateEntry($e)) {
                throw $e;
            }

            $application = SignApplication::where('student_id', $studentId)->first();
            if ($application) {
                return $this->updateDraft($application, $data);
            }

            throw $e;
        }
    }

    /**
     * 更新草稿：只更新有值的字段
     */
    private function updateDraft(SignApplication $application, array $data): SignApplication
    {
        $fillData = array_filter($data, fn($v) => $v !== null);
        if (isset($fillData['department'])) {
            $fillData['lab_id'] = $fillData['department'] == 2 ? 'ai' : 'software';
        }
        $application->fill($fillData);
        $application->save();

        return $application;
    }

    /**
     * 判断是否为 MySQL 唯一索引冲突（ER_DUP_ENTRY = 1062）
     */
    private function isDuplicateEntry(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1062;
    }

    /**
     * 格式化报名数据输出
     */
    private function formatApplication(SignApplication $application): array
    {
        return [
            'id'                => $application->id,
            'userId'            => $application->user_id,
            'name'              => $application->name,
            'studentId'         => $application->student_id,
            'department'        => $application->department,
            'departmentName'    => $application->department === 1 ? '软件开发实验室' : '人工智能实验室',
            'college'           => $application->college,
            'major'             => $application->major,
            'className'         => $application->class_name,
            'phone'             => $application->phone,
            'selfIntroduction'  => $application->self_introduction,
            'status'            => $application->status,
            'statusName'        => $application->status === 0 ? '草稿' : '已提交',
            'auditStatus'       => $application->audit_status,
            'auditStatusName'   => match ((int) $application->audit_status) {
                0 => '待审核',
                1 => '已通过',
                2 => '已驳回',
                default => '',
            },
            'auditRemark'       => $application->audit_remark,
            'auditTime'         => $application->audit_time,
            'submitTime'        => $application->submit_time,
            'createTime'        => $application->create_time,
            'updateTime'        => $application->update_time,
        ];
    }
}
