<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\SignApplication;
use App\Models\TrainCourse;
use App\Models\TrainSign;
use App\Traits\LogTrait;
use Illuminate\Http\UploadedFile;
use OSS\Core\OssException;
use OSS\Http\RequestCore_Exception;
use OSS\OssClient;

class CourseService
{
    use LogTrait;

    public function create(int $adminId, array $data): array
    {
        $coverImg = $data['coverImg'] ?? '';
        if (request()->hasFile('cover')) {
            $coverImg = $this->uploadCover(request()->file('cover'));
        }

        $course = TrainCourse::create([
            'course_name'  => $data['courseName'],
            'course_desc'  => $data['courseDesc'] ?? '',
            'instructor'   => $data['instructor'] ?? null,
            'course_date'  => $data['courseDate'] ?? null,
            'location'     => $data['location'] ?? null,
            'cover_img'    => $coverImg,
            'start_time'   => $data['startTime'] ?? null,
            'end_time'     => $data['endTime'] ?? null,
            'max_sign'     => $data['maxSign'] ?? 100,
            'group_count'  => $data['groupCount'] ?? 1,
            'group_name'   => $data['groupName'] ?? null,
            'status'       => 1,
            'create_admin' => $adminId,
            'create_time'  => now(),
        ]);

        // 若指定了班级，自动为该班级下所有已通过审核的学生创建报名记录
        if (!empty($data['groupName'] ?? null)) {
            $this->syncStudentsToCourse($course, $data['groupName'], $adminId);
        }

        $this->logBusiness('管理员创建课程', ['admin_id' => $adminId, 'course_id' => $course->course_id]);

        return ['courseId' => $course->course_id, 'courseName' => $course->course_name];
    }

    public function update(int $courseId, array $data): array
    {
        $course = TrainCourse::find($courseId);
        if (!$course) throw new BusinessException('课程不存在', ResponseCode::DATA_NOT_FOUND);

        if (request()->hasFile('cover')) {
            $course->cover_img = $this->uploadCover(request()->file('cover'));
        }

        $oldGroupName = $course->group_name;

        $map = ['courseName' => 'course_name', 'courseDesc' => 'course_desc', 'instructor' => 'instructor', 'courseDate' => 'course_date', 'location' => 'location', 'coverImg' => 'cover_img', 'startTime' => 'start_time', 'endTime' => 'end_time', 'maxSign' => 'max_sign', 'groupCount' => 'group_count', 'groupName' => 'group_name', 'status' => 'status'];
        foreach ($map as $key => $col) {
            if (array_key_exists($key, $data)) $course->{$col} = $data[$key];
        }
        $course->save();

        // 若 groupName 有变化，同步该班级学生到课程
        if (!empty($course->group_name) && $course->group_name !== $oldGroupName) {
            $adminId = auth('admin_api')->user()->admin_id ?? $course->create_admin;
            $this->syncStudentsToCourse($course, $course->group_name, $adminId);
        }

        $this->logBusiness('管理员编辑课程', ['course_id' => $courseId]);
        return ['courseId' => $course->course_id, 'courseName' => $course->course_name];
    }

    public function delete(int $courseId): void
    {
        $course = TrainCourse::find($courseId);
        if (!$course) throw new BusinessException('课程不存在', ResponseCode::DATA_NOT_FOUND);
        $course->delete();
        $this->logBusiness('管理员删除课程', ['course_id' => $courseId]);
    }

    /**
     * 课程创建后，自动将该班级下所有已通过审核的学生同步到 train_sign
     */
    private function syncStudentsToCourse(TrainCourse $course, string $groupName, int $adminId): void
    {
        $admin = \App\Models\SysAdmin::find($adminId);
        $dept = $admin ? $admin->department : null;

        // 查询该部门下该班级所有已通过审核的学生
        $apps = SignApplication::where('status', 1)
            ->where('audit_status', 1)
            ->where('group_name', $groupName)
            ->when($dept, fn($q) => $q->where('department', $dept))
            ->whereNotNull('user_id')
            ->get();

        $count = 0;
        foreach ($apps as $app) {
            $exists = TrainSign::where('user_id', $app->user_id)
                ->where('course_id', $course->course_id)
                ->exists();
            if (!$exists) {
                TrainSign::create([
                    'user_id'    => $app->user_id,
                    'course_id'  => $course->course_id,
                    'group_name' => $groupName,
                    'status'     => 1,
                    'sign_time'  => now(),
                ]);
                $count++;
            }
        }

        if ($count > 0) {
            $this->logBusiness('自动同步学生到课程', [
                'course_id' => $course->course_id,
                'group_name' => $groupName,
                'synced' => $count,
            ]);
        }
    }

    public function list(int $page = 1, int $size = 10, ?string $courseName = null, ?int $status = null): array
    {
        $query = TrainCourse::orderBy('create_time', 'desc');
        if ($courseName) $query->where('course_name', 'like', "%{$courseName}%");
        if ($status !== null) $query->where('status', $status);

        $total = $query->count();
        $list = $query->forPage($page, $size)->get()->map(function (TrainCourse $c) {
            return [
                'courseId'    => $c->course_id,
                'courseName'  => $c->course_name,
                'courseDesc'  => $c->course_desc,
                'instructor'  => $c->instructor,
                'courseDate'  => $c->course_date,
                'location'    => $c->location,
                'coverImg'    => $c->cover_img,
                'startTime'   => $c->start_time,
                'endTime'     => $c->end_time,
                'maxSign'     => $c->max_sign,
                'groupCount'  => $c->group_count,
                'groupName'   => $c->group_name,
                'status'      => $c->status,
                'signCount'   => TrainSign::where('course_id', $c->course_id)->where('status', 1)->count(),
                'createTime'  => $c->create_time,
            ];
        });

        return ['total' => $total, 'list' => $list->values()];
    }

    public function detail(int $courseId): array
    {
        $c = TrainCourse::find($courseId);
        if (!$c) throw new BusinessException('课程不存在', ResponseCode::DATA_NOT_FOUND);
        return [
            'courseId'    => $c->course_id,
            'courseName'  => $c->course_name,
            'courseDesc'  => $c->course_desc,
            'instructor'  => $c->instructor,
            'courseDate'  => $c->course_date,
            'location'    => $c->location,
            'coverImg'    => $c->cover_img,
            'startTime'   => $c->start_time,
            'endTime'     => $c->end_time,
            'maxSign'     => $c->max_sign,
            'groupCount'  => $c->group_count,
            'groupName'   => $c->group_name,
            'status'      => $c->status,
            'signCount'   => TrainSign::where('course_id', $c->course_id)->where('status', 1)->count(),
            'createTime'  => $c->create_time,
        ];
    }

    public function status(int $courseId, int $status): array
    {
        $course = TrainCourse::find($courseId);
        if (!$course) throw new BusinessException('课程不存在', ResponseCode::DATA_NOT_FOUND);
        $course->status = $status;
        $course->save();
        $this->logBusiness($status ? '课程上架' : '课程下架', ['course_id' => $courseId]);
        return ['courseId' => $course->course_id, 'status' => $status, 'statusText' => $status ? '已上架' : '已下架'];
    }

    /**
     * 上传封面到 OSS
     */
    private function uploadCover(UploadedFile $file): string
    {
        $ext    = $file->getClientOriginalExtension();
        $object = 'course-covers/' . uniqid() . '.' . $ext;

        try {
            $oss = new OssClient(
                config('filesystems.disks.oss.access_id'),
                config('filesystems.disks.oss.access_key'),
                config('filesystems.disks.oss.endpoint'),
            );
            $oss->putObject(config('filesystems.disks.oss.bucket'), $object, $file->getContent(), [
                OssClient::OSS_HEADERS => ['Content-Disposition' => 'inline'],
            ]);
        } catch (OssException | RequestCore_Exception $e) {
            \Illuminate\Support\Facades\Log::channel('exception')->error('OSS上传课程封面失败', [
                'object'  => $object,
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);
            throw new BusinessException('封面上传失败，请稍后重试', ResponseCode::THIRD_PARTY_ERROR);
        }

        return 'https://' . config('filesystems.disks.oss.bucket') . '.'
             . config('filesystems.disks.oss.endpoint') . '/' . $object;
    }
}
