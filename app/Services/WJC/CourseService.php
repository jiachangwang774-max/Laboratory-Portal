<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\TrainCourse;
use App\Models\TrainSign;
use App\Traits\LogTrait;
use Illuminate\Http\UploadedFile;
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
            'cover_img'    => $coverImg,
            'start_time'   => $data['startTime'] ?? null,
            'end_time'     => $data['endTime'] ?? null,
            'max_sign'     => $data['maxSign'] ?? 100,
            'status'       => 1,
            'create_admin' => $adminId,
            'create_time'  => now(),
        ]);

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

        $map = ['courseName' => 'course_name', 'courseDesc' => 'course_desc', 'coverImg' => 'cover_img', 'startTime' => 'start_time', 'endTime' => 'end_time', 'maxSign' => 'max_sign', 'status' => 'status'];
        foreach ($map as $key => $col) {
            if (isset($data[$key])) $course->{$col} = $data[$key];
        }
        $course->save();

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
                'coverImg'    => $c->cover_img,
                'startTime'   => $c->start_time,
                'endTime'     => $c->end_time,
                'maxSign'     => $c->max_sign,
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
            'coverImg'    => $c->cover_img,
            'startTime'   => $c->start_time,
            'endTime'     => $c->end_time,
            'maxSign'     => $c->max_sign,
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

        $oss = new OssClient(
            config('filesystems.disks.oss.access_id'),
            config('filesystems.disks.oss.access_key'),
            config('filesystems.disks.oss.endpoint'),
        );
        $oss->putObject(config('filesystems.disks.oss.bucket'), $object, $file->getContent());

        return 'https://' . config('filesystems.disks.oss.bucket') . '.'
             . config('filesystems.disks.oss.endpoint') . '/' . $object;
    }
}
