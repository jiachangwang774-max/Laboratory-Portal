<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\CourseSession;
use App\Traits\LogTrait;

class SessionService
{
    use LogTrait;

    public function create(int $adminId, array $data): array
    {
        $s = CourseSession::create([
            'course_id'    => $data['courseId'],
            'title'        => $data['title'],
            'content'      => $data['content'] ?? '',
            'session_date' => $data['sessionDate'] ?? null,
            'end_time'     => $data['endTime'] ?? null,
            'location'     => $data['location'] ?? null,
            'instructor'   => $data['instructor'] ?? null,
            'sort_order'   => $data['sortOrder'] ?? 0,
            'status'       => 1,
            'create_admin' => $adminId,
            'create_time'  => now(),
        ]);
        $this->logBusiness('管理员发布课程安排', ['admin_id' => $adminId, 'session_id' => $s->session_id]);
        return ['sessionId' => $s->session_id, 'title' => $s->title];
    }

    public function update(int $sessionId, array $data): array
    {
        $s = CourseSession::find($sessionId);
        if (!$s) throw new BusinessException('课程安排不存在', ResponseCode::DATA_NOT_FOUND);

        $map = ['title'=>'title','content'=>'content','sessionDate'=>'session_date','endTime'=>'end_time','location'=>'location','instructor'=>'instructor','sortOrder'=>'sort_order','status'=>'status'];
        foreach ($map as $key => $col) {
            if (array_key_exists($key, $data)) $s->{$col} = $data[$key];
        }
        $s->update_time = now();
        $s->save();
        return ['sessionId' => $s->session_id, 'title' => $s->title];
    }

    public function delete(int $sessionId): void
    {
        $s = CourseSession::find($sessionId);
        if (!$s) throw new BusinessException('课程安排不存在', ResponseCode::DATA_NOT_FOUND);
        $s->delete();
        $this->logBusiness('管理员删除课程安排', ['session_id' => $sessionId]);
    }

    public function list(int $page = 1, int $size = 10, ?int $courseId = null): array
    {
        $query = CourseSession::with('course')->orderBy('sort_order')->orderBy('session_date', 'desc');
        if ($courseId) $query->where('course_id', $courseId);

        $total = $query->count();
        $list  = $query->forPage($page, $size)->get()->map(function (CourseSession $s) {
            return [
                'sessionId'   => $s->session_id, 'courseId' => $s->course_id,
                'courseName'  => $s->course->course_name ?? '',
                'title'       => $s->title, 'content' => $s->content,
                'sessionDate' => $s->session_date, 'endTime' => $s->end_time,
                'location'    => $s->location, 'instructor' => $s->instructor,
                'status'      => $s->status, 'sortOrder' => $s->sort_order,
                'createTime'  => $s->create_time,
            ];
        });
        return ['total' => $total, 'list' => $list->values()];
    }

    public function detail(int $sessionId): array
    {
        $s = CourseSession::with('course')->find($sessionId);
        if (!$s) throw new BusinessException('课程安排不存在', ResponseCode::DATA_NOT_FOUND);
        return [
            'sessionId'   => $s->session_id, 'courseId' => $s->course_id,
            'courseName'  => $s->course->course_name ?? '',
            'title'       => $s->title, 'content' => $s->content,
            'sessionDate' => $s->session_date, 'endTime' => $s->end_time,
            'location'    => $s->location, 'instructor' => $s->instructor,
            'status'      => $s->status, 'sortOrder' => $s->sort_order,
            'createTime'  => $s->create_time,
        ];
    }
}
