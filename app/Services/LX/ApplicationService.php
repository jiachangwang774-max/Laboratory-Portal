<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\SignApplication;
use App\Traits\LogTrait;

class ApplicationService
{
    use LogTrait;

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

            // 只更新有值的字段
            $fillData = array_filter($data, fn($v) => $v !== null);
            $application->fill($fillData);
            $application->save();

            $this->logBusiness('报名草稿更新', [
                'student_id' => $studentId,
                'app_id'     => $application->id,
            ]);
        } else {
            $application = SignApplication::create([
                'student_id' => $studentId,
                'status'     => 0,
                ...$data,
            ]);

            $this->logBusiness('报名草稿创建', [
                'student_id' => $studentId,
                'app_id'     => $application->id,
            ]);
        }

        return $this->formatApplication($application);
    }

    /**
     * 提交报名（所有字段必填，由 FormRequest 保证）
     */
    public function submit(array $data): array
    {
        $studentId = $data['student_id'];
        $application = SignApplication::where('student_id', $studentId)->first();

        if ($application && $application->status === 1) {
            throw new BusinessException('报名已提交，请勿重复操作', ResponseCode::DUPLICATE_SUBMIT);
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
            $application->fill($fillData);
            $application->save();
        } else {
            $application = SignApplication::create($fillData);
        }

        $this->logBusiness('报名提交成功', [
            'student_id' => $studentId,
            'app_id'     => $application->id,
            'department' => $data['department'],
        ]);

        return $this->formatApplication($application);
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
            'submitTime'        => $application->submit_time,
            'createTime'        => $application->create_time,
            'updateTime'        => $application->update_time,
        ];
    }
}
