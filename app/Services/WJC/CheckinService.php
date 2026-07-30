<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\CheckinRecord;
use App\Models\CourseCheckin;
use App\Models\TrainSign;
use App\Traits\LogTrait;

class CheckinService
{
    use LogTrait;

    /**
     * 发起签到，生成6位随机码
     */
    public function create(int $adminId, int $courseId, ?int $sessionId, int $duration = 5): array
    {
        // 检查是否有进行中的签到，有则先结束
        CourseCheckin::where('course_id', $courseId)->where('status', 1)->update(['status' => 0, 'end_time' => now()]);

        $code = (string) random_int(100000, 999999);

        $c = CourseCheckin::create([
            'course_id'    => $courseId,
            'session_id'   => $sessionId,
            'checkin_code' => $code,
            'status'       => 1,
            'create_admin' => $adminId,
            'create_time'  => now(),
            'end_time'     => now()->addMinutes($duration),
        ]);

        $this->logBusiness('管理员发起签到', ['admin_id' => $adminId, 'checkin_id' => $c->checkin_id, 'code' => $code, 'duration' => $duration]);

        return ['checkinId' => $c->checkin_id, 'checkinCode' => $code, 'courseId' => $courseId, 'endTime' => $c->end_time];
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
        if ($c->checkin_code !== $code) throw new BusinessException('签到码错误', ResponseCode::PARAM_ERROR);

        CheckinRecord::firstOrCreate(
            ['checkin_id' => $checkinId, 'user_id' => $userId],
            ['checkin_method' => 'code', 'checkin_time' => now()]
        );

        return ['checkinId' => $checkinId, 'statusText' => '签到成功'];
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
     * 结束签到
     */
    public function close(int $checkinId): void
    {
        $c = CourseCheckin::find($checkinId);
        if (!$c) throw new BusinessException('签到不存在', ResponseCode::DATA_NOT_FOUND);
        $c->status = 0;
        $c->end_time = now();
        $c->save();
    }

    /**
     * 签到列表
     */
    public function list(int $page = 1, int $size = 10, ?int $courseId = null, ?int $sessionId = null): array
    {
        $query = CourseCheckin::with('course')->orderBy('create_time', 'desc');
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
     * 签到记录
     */
    public function records(int $checkinId): array
    {
        $c = CourseCheckin::with('course')->find($checkinId);
        if (!$c) throw new BusinessException('签到不存在', ResponseCode::DATA_NOT_FOUND);

        $signedIds = CheckinRecord::where('checkin_id', $checkinId)->pluck('user_id');

        $signUsers = TrainSign::where('course_id', $c->course_id)->where('status', 1)
            ->with('user')->get()->map(function ($s) use ($signedIds) {
                $user = $s->user;
                $isSigned = $signedIds->contains($user->user_id);
                $record = $isSigned ? CheckinRecord::where('checkin_id', $s->checkin_id ?? 0)->where('user_id', $user->user_id)->first() : null;
                return [
                    'userId'     => $user->user_id,
                    'realName'   => $user->real_name,
                    'studentId'  => $user->student_id,
                    'college'    => $user->college,
                    'major'      => $user->major,
                    'isSigned'   => $isSigned,
                    'method'     => $record->checkin_method ?? null,
                    'checkinTime'=> $record->checkin_time ?? null,
                ];
            });

        return [
            'checkinId'   => $c->checkin_id,
            'courseName'  => $c->course->course_name ?? '',
            'checkinCode' => $c->checkin_code,
            'status'      => $c->status,
            'total'       => $signUsers->count(),
            'signed'      => $signedIds->count(),
            'list'        => $signUsers->values(),
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
                '学院'   => $r['college'],
                '专业'   => $r['major'],
                '签到状态' => $r['isSigned'] ? '已签到' : '未签到',
                '签到方式' => $r['isSigned'] ? ($r['method'] === 'code' ? '扫码' : '手动') : '—',
                '签到时间' => $r['checkinTime'] ?? '—',
            ];
        })->toArray();
    }
}
