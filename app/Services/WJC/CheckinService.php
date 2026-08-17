<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\CheckinRecord;
use App\Models\CourseCheckin;
use App\Models\SysUser;
use App\Models\TrainSign;
use App\Traits\LogTrait;

class CheckinService
{
    use LogTrait;

    /**
     * 发起签到，生成6位随机码
     */
    public function create(int $adminId, int $courseId, ?int $sessionId, ?int $duration = 5, ?string $endTime = null, ?string $title = null, ?string $className = null): array
    {
        $labId = auth('admin_api')->user()->lab_id ?? 'software';

        // 检查是否有进行中的签到，有则先结束
        CourseCheckin::where('lab_id', $labId)->where('course_id', $courseId)->where('status', 1)->update(['status' => 0, 'end_time' => now()]);

        $code = (string) random_int(100000, 999999);

        if ($endTime) {
            try {
                $endTimeCarbon = \Carbon\Carbon::parse($endTime);
            } catch (\Throwable $e) {
                $endTimeCarbon = now()->addMinutes((int) $duration);
            }
        } else {
            $endTimeCarbon = now()->addMinutes((int) $duration);
        }

        $c = CourseCheckin::create([
            'course_id'    => $courseId,
            'session_id'   => $sessionId,
            'checkin_code' => $code,
            'status'       => 1,
            'lab_id'       => $labId,
            'create_admin' => $adminId,
            'create_time'  => now(),
            'end_time'     => $endTimeCarbon,
            'title'        => $title,
            'class_name'   => $className,
        ]);

        $this->logBusiness('管理员发起签到', ['admin_id' => $adminId, 'checkin_id' => $c->checkin_id, 'code' => $code, 'duration' => $duration]);

        return ['checkinId' => $c->checkin_id, 'checkinCode' => $code, 'courseId' => $courseId, 'endTime' => $c->end_time, 'title' => $c->title, 'className' => $c->class_name];
    }

    /**
     * 自动检测签到是否已过期
     */
    private function autoCloseExpired(CourseCheckin $c): void
    {
        if ($c->status === 1 && $c->end_time && now()->gt($c->end_time)) {
            $c->status = 0;
            $c->save();
        }
    }

    /**
     * 学生扫码签到
     */
    public function studentCheckin(int $userId, int $checkinId, string $code): array
    {
        $c = CourseCheckin::find($checkinId);
        if (!$c || $c->status !== 1) throw new BusinessException('签到已结束', ResponseCode::BUSINESS_ERROR);
        if ($c->end_time && now()->gt($c->end_time)) throw new BusinessException('签到已超时', ResponseCode::BUSINESS_ERROR);
        if ($c->checkin_code !== $code) throw new BusinessException('签到码错误', ResponseCode::PARAM_ERROR);

        CheckinRecord::firstOrCreate(
            ['checkin_id' => $checkinId, 'user_id' => $userId],
            ['checkin_method' => 'code', 'checkin_time' => now()]
        );

        return ['checkinId' => $checkinId, 'statusText' => '签到成功'];
    }

    /**
     * 学生通过课程ID + 签到码签到
     *
     * 校验：课程下存在有效签到码 → 签到未结束 → 学生已报名该课程
     */
    public function studentCheckinByCode(int $userId, int $courseId, string $code): array
    {
        // 校验学生是否已报名该课程
        $enrolled = \App\Models\TrainSign::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', 1)
            ->exists();

        if (!$enrolled) {
            throw new BusinessException('您未报名该课程，无法签到', ResponseCode::FORBIDDEN);
        }

        // 根据课程ID + 签到码查找进行中的签到
        $c = CourseCheckin::where('course_id', $courseId)
            ->where('checkin_code', $code)
            ->where('status', 1)
            ->where('end_time', '>', now())
            ->first();

        if (!$c) {
            throw new BusinessException('签到码无效或签到已结束', ResponseCode::BUSINESS_ERROR);
        }

        CheckinRecord::firstOrCreate(
            ['checkin_id' => $c->checkin_id, 'user_id' => $userId],
            ['checkin_method' => 'code', 'checkin_time' => now()]
        );

        $this->logBusiness('学生扫码签到', ['user_id' => $userId, 'checkin_id' => $c->checkin_id, 'course_id' => $courseId, 'code' => $code]);

        return [
            'checkinId'  => $c->checkin_id,
            'courseId'   => $c->course_id,
            'sessionId'  => $c->session_id,
            'statusText' => '签到成功',
        ];
    }

    /**
     * 管理员手动签到
     */
    public function manualCheckin(int $checkinId, int $userId): array
    {
        $c = CourseCheckin::find($checkinId);
        if (!$c || $c->status !== 1) throw new BusinessException('签到已结束', ResponseCode::BUSINESS_ERROR);

        CheckinRecord::firstOrCreate(
            ['checkin_id' => $checkinId, 'user_id' => $userId],
            ['checkin_method' => 'manual', 'checkin_time' => now()]
        );

        return ['checkinId' => $checkinId, 'userId' => $userId, 'statusText' => '手动签到成功'];
    }

    /**
     * 管理员批量签到（按学号）
     *
     * 学号去重后逐一校验：用户存在 → 已报名该课程 → 写入签到记录。
     * 返回成功与失败明细。
     */
    public function batchCheckin(int $checkinId, array $studentIds): array
    {
        $labId = auth('admin_api')->user()->lab_id ?? 'software';

        $c = CourseCheckin::find($checkinId);
        if (!$c) throw new BusinessException('签到不存在', ResponseCode::DATA_NOT_FOUND);
        if ($c->status !== 1) throw new BusinessException('签到已结束', ResponseCode::BUSINESS_ERROR);
        if ($c->end_time && now()->gt($c->end_time)) throw new BusinessException('签到已超时', ResponseCode::BUSINESS_ERROR);
        if ($c->lab_id !== $labId) throw new BusinessException('无权操作', ResponseCode::FORBIDDEN);

        // 学号去重、去空
        $studentIds = array_values(array_unique(array_filter(array_map('trim', $studentIds), 'strlen')));

        $users = SysUser::whereIn('student_id', $studentIds)->get()->keyBy('student_id');

        $success = [];
        $failed  = [];

        foreach ($studentIds as $studentId) {
            $user = $users->get($studentId);

            if (!$user) {
                $failed[] = ['studentId' => $studentId, 'reason' => '学号不存在'];
                continue;
            }

            $enrolled = TrainSign::where('user_id', $user->user_id)
                ->where('course_id', $c->course_id)
                ->where('status', 1)
                ->exists();

            if (!$enrolled) {
                $failed[] = ['studentId' => $studentId, 'reason' => '未报名该课程'];
                continue;
            }

            CheckinRecord::firstOrCreate(
                ['checkin_id' => $checkinId, 'user_id' => $user->user_id],
                ['checkin_method' => 'manual', 'checkin_time' => now(), 'lab_id' => $labId]
            );

            $success[] = $studentId;
        }

        $this->logBusiness('管理员批量签到', [
            'admin_id'   => auth('admin_api')->user()->admin_id,
            'checkin_id' => $checkinId,
            'success'    => count($success),
            'failed'     => count($failed),
        ]);

        return [
            'checkinId'    => $checkinId,
            'total'        => count($studentIds),
            'successCount' => count($success),
            'failedCount'  => count($failed),
            'successList'  => $success,
            'failedList'   => $failed,
        ];
    }

    /**
     * 结束签到
     */
    public function close(int $checkinId): void
    {
        $c = CourseCheckin::find($checkinId);
        if (!$c) throw new BusinessException('签到不存在', ResponseCode::DATA_NOT_FOUND);
        if ($c->lab_id !== (auth('admin_api')->user()->lab_id ?? 'software')) throw new BusinessException('无权操作', ResponseCode::FORBIDDEN);
        $c->status = 0;
        $c->end_time = now();
        $c->save();
    }

    public function delete(int $checkinId): void
    {
        $c = CourseCheckin::find($checkinId);
        if (!$c) throw new BusinessException('签到不存在', ResponseCode::DATA_NOT_FOUND);
        if ($c->lab_id !== (auth('admin_api')->user()->lab_id ?? 'software')) throw new BusinessException('无权操作', ResponseCode::FORBIDDEN);
        CheckinRecord::where('checkin_id', $checkinId)->delete();
        $c->delete();
        $this->logBusiness('管理员删除签到', ['checkin_id' => $checkinId]);
    }

    /**
     * 签到列表
     */
    public function list(int $page = 1, int $size = 10, ?int $courseId = null, ?int $sessionId = null): array
    {
        $labId = auth('admin_api')->user()->lab_id ?? 'software';
        $query = CourseCheckin::with('course')->where('lab_id', $labId)->orderBy('create_time', 'desc');
        if ($courseId) $query->where('course_id', $courseId);
        if ($sessionId) $query->where('session_id', $sessionId);

        $total = $query->count();
        $list  = $query->forPage($page, $size)->get()->map(function (CourseCheckin $c) {
            $this->autoCloseExpired($c);
            $signedCount = CheckinRecord::where('checkin_id', $c->checkin_id)->count();
            return [
                'checkinId'   => $c->checkin_id,
                'courseId'    => $c->course_id,
                'courseName'  => $c->course->course_name ?? '',
                'sessionId'   => $c->session_id,
                'checkinCode' => $c->checkin_code,
                'title'       => $c->title ?? '',
                'className'   => $c->class_name ?? '',
                'status'      => $c->status,
                'statusText'  => $c->status ? '进行中' : '已结束',
                'signedCount' => $signedCount,
                'createTime'  => $c->create_time,
                'endTime'     => $c->end_time,
            ];
        });

        return ['total' => $total, 'list' => $list->values()];
    }

    /**
     * 签到记录（列出报名该课程的学员及签到状态）
     */
    public function records(int $checkinId): array
    {
        $c = CourseCheckin::with('course')->find($checkinId);
        if (!$c) throw new BusinessException('签到不存在', ResponseCode::DATA_NOT_FOUND);
        if ($c->lab_id !== (auth('admin_api')->user()->lab_id ?? 'software')) throw new BusinessException('无权操作', ResponseCode::FORBIDDEN);

        $signedIds = CheckinRecord::where('checkin_id', $checkinId)->pluck('user_id');

        $list = TrainSign::with('user')
            ->where('course_id', $c->course_id)
            ->where('status', 1)
            ->orderBy('group_name')
            ->orderBy('sign_time')
            ->get()
            ->map(function (TrainSign $sign) use ($signedIds, $checkinId) {
                $isSigned = $signedIds->contains($sign->user_id);
                $record = $isSigned
                    ? CheckinRecord::where('checkin_id', $checkinId)->where('user_id', $sign->user_id)->first()
                    : null;
                return [
                    'userId'      => $sign->user_id,
                    'realName'    => $sign->user->real_name ?? '',
                    'studentId'   => $sign->user->student_id ?? '',
                    'college'     => $sign->user->college ?? '',
                    'major'       => $sign->user->major ?? '',
                    'className'   => $sign->group_name ?? '',
                    'isSigned'    => $isSigned,
                    'method'      => $record->checkin_method ?? null,
                    'checkinTime' => $record->checkin_time ?? null,
                ];
            });

        return [
            'checkinId'   => $c->checkin_id,
            'courseName'  => $c->course->course_name ?? '',
            'checkinCode' => $c->checkin_code,
            'status'      => $c->status,
            'total'       => $list->count(),
            'signed'      => $list->where('isSigned', true)->count(),
            'list'        => $list->values(),
        ];
    }

    /**
     * 导出签到名单
     */
    public function export(int $checkinId): array
    {
        $data = $this->records($checkinId);
        return $data['list']->map(function ($r) {
            return [
                '姓名'   => $r['realName'],
                '学号'   => $r['studentId'],
                '班级'   => $r['className'] ?? '',
                '学院'   => $r['college'],
                '专业'   => $r['major'],
                '签到状态' => $r['isSigned'] ? '已签到' : '未签到',
                '签到方式' => $r['isSigned'] ? ($r['method'] === 'code' ? '扫码' : '手动') : '—',
                '签到时间' => $r['checkinTime'] ?? '—',
            ];
        })->toArray();
    }
}
