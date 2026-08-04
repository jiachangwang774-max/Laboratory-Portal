<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\CourseSession;
use App\Models\HomeworkSubmit;
use App\Models\SignApplication;
use App\Models\TrainCourse;
use App\Models\TrainHomework;
use App\Models\TrainSign;
use App\Traits\LogTrait;
use OSS\Core\OssException;
use OSS\Http\RequestCore_Exception;
use OSS\OssClient;

class TrainService
{
    use LogTrait;

    /**
     * 学生端课程详情
     *
     * 仅返回 status=1 的上架课程，同时返回当前学生的报名状态
     */
    public function courseDetail(int $courseId, int $userId): array
    {
        $course = TrainCourse::enabled()->find($courseId);

        if (!$course) {
            throw new BusinessException('课程不存在或已下架', ResponseCode::DATA_NOT_FOUND);
        }

        // 查询当前学生对该课程的报名状态
        $sign = TrainSign::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        // 已报名人数
        $signCount = TrainSign::where('course_id', $courseId)
            ->where('status', 1)
            ->count();

        return [
            'courseId'    => $course->course_id,
            'courseName'  => $course->course_name,
            'courseDesc'  => $course->course_desc,
            'coverImg'    => $course->cover_img,
            'instructor'  => $course->instructor,
            'courseDate'  => $course->course_date,
            'location'    => $course->location,
            'startTime'   => $course->start_time,
            'endTime'     => $course->end_time,
            'maxSign'     => $course->max_sign,
            'signCount'   => $signCount,
            'signStatus'  => $sign ? $sign->status : null,   // null=未报名, 1=已报名, 0=已取消
            'signTime'    => $sign ? $sign->sign_time : null,
        ];
    }

    /**
     * 我的课程详情（学生端）
     *
     * 获取当前登录学生通过报名审核后随机分配的课程详情及班级号。
     * 优先查 train_sign，若无记录则按班级号直接匹配课程并自动补建报名记录。
     */
    public function myCourse(int $userId): array
    {
        $user = \App\Models\SysUser::find($userId);

        if (!$user) {
            throw new BusinessException('用户不存在', ResponseCode::DATA_NOT_FOUND);
        }

        // 查找用户已通过审核的报名申请（获取班级号）
        $application = SignApplication::where('audit_status', 1)
            ->where(function ($q) use ($userId, $user) {
                $q->where('user_id', $userId)
                  ->orWhere('student_id', $user->student_id);
            })
            ->latest('audit_time')
            ->first();

        if (!$application) {
            throw new BusinessException('未找到您的报名记录，请先完成报名', ResponseCode::DATA_NOT_FOUND);
        }

        // 查找用户的课程报名记录
        $sign = TrainSign::where('user_id', $userId)
            ->where('status', 1)
            ->with('course')
            ->latest('sign_time')
            ->first();

        // 若无 train_sign 记录，按班级号直接匹配课程并自动补建
        if (!$sign || !$sign->course) {
            if (empty($application->group_name)) {
                throw new BusinessException('您尚未被分配到班级，请联系管理员', ResponseCode::DATA_NOT_FOUND);
            }

            $course = TrainCourse::enabled()
                ->where('group_name', $application->group_name)
                ->latest('create_time')
                ->first();

            if (!$course) {
                throw new BusinessException('您所在班级暂无课程，请等待管理员发布', ResponseCode::DATA_NOT_FOUND);
            }

            // 自动补建 train_sign 记录
            $sign = TrainSign::firstOrCreate(
                ['user_id' => $userId, 'course_id' => $course->course_id],
                ['group_name' => $application->group_name, 'status' => 1, 'sign_time' => now()]
            );
        }

        $course = $sign->course ?? TrainCourse::find($sign->course_id);

        // 已报名人数
        $signCount = TrainSign::where('course_id', $course->course_id)
            ->where('status', 1)
            ->count();

        return [
            'courseId'    => $course->course_id,
            'courseName'  => $course->course_name,
            'courseDesc'  => $course->course_desc,
            'coverImg'    => $course->cover_img,
            'instructor'  => $course->instructor,
            'courseDate'  => $course->course_date,
            'location'    => $course->location,
            'startTime'   => $course->start_time,
            'endTime'     => $course->end_time,
            'maxSign'     => $course->max_sign,
            'signCount'   => $signCount,
            'signStatus'  => $sign->status,
            'signTime'    => $sign->sign_time,
            'groupName'   => $application->group_name,    // 管理员端随机分配的班级号
        ];
    }

    /**
     * 培训详情列表（所有开放的课程安排）
     *
     * 仅返回 status=1 的课程安排，按排序字段升序
     */
    public function trainingDetail(): array
    {
        $sessions = CourseSession::enabled()
            ->orderBy('sort_order')
            ->orderBy('session_date')
            ->get()
            ->map(function (CourseSession $session) {
                return [
                    'sessionId'   => $session->session_id,
                    'courseId'    => $session->course_id,
                    'courseName'  => $session->course->course_name ?? '',
                    'title'       => $session->title,
                    'content'     => $session->content,
                    'sessionDate' => $session->session_date,
                    'endTime'     => $session->end_time,
                    'location'    => $session->location,
                    'instructor'  => $session->instructor,
                ];
            });

        return $sessions->values()->toArray();
    }

    /**
     * 单个培训详情
     *
     * 仅返回 status=1 的课程安排
     */
    public function trainingSessionDetail(int $sessionId): array
    {
        $session = CourseSession::enabled()->find($sessionId);

        if (!$session) {
            throw new BusinessException('课程安排不存在或已下架', ResponseCode::DATA_NOT_FOUND);
        }

        return [
            'sessionId'   => $session->session_id,
            'courseId'    => $session->course_id,
            'courseName'  => $session->course->course_name ?? '',
            'title'       => $session->title,
            'content'     => $session->content,
            'sessionDate' => $session->session_date,
            'endTime'     => $session->end_time,
            'location'    => $session->location,
            'instructor'  => $session->instructor,
        ];
    }

    /**
     * 单个作业详情（含当前学生的提交记录、评分、批注）
     */
    public function homeworkDetail(int $homeworkId, int $userId): array
    {
        $homework = TrainHomework::find($homeworkId);

        if (!$homework) {
            throw new BusinessException('作业不存在', ResponseCode::DATA_NOT_FOUND);
        }

        $submit = HomeworkSubmit::where('user_id', $userId)
            ->where('homework_id', $homeworkId)
            ->first();

        return [
            'homeworkId'      => $homework->homework_id,
            'courseId'        => $homework->course_id,
            'courseName'      => $homework->course->course_name ?? '',
            'homeworkTitle'   => $homework->homework_title,
            'homeworkContent' => $homework->homework_content,
            'deadline'        => $homework->deadline,
            'createTime'      => $homework->create_time,
            'submitId'        => $submit ? $submit->submit_id : null,
            'submitContent'   => $submit ? $submit->submit_content : null,
            'submitFile'      => $submit ? $submit->submit_file : null,
            'submitTime'      => $submit ? $submit->submit_time : null,
            'score'           => $submit ? $submit->score : null,
            'remark'          => $submit ? $submit->remark : null,
        ];
    }

    /**
     * 待完成作业列表（未提交的作业）
     */
    public function homeworkPending(int $userId): array
    {
        $courseIds = $this->getUserCourseIds($userId);

        if (empty($courseIds)) {
            return [];
        }

        // 已提交的作业ID
        $submittedIds = HomeworkSubmit::where('user_id', $userId)
            ->pluck('homework_id')
            ->toArray();

        return TrainHomework::whereIn('course_id', $courseIds)
            ->whereNotIn('homework_id', $submittedIds)
            ->orderBy('create_time', 'desc')
            ->get()
            ->map(function (TrainHomework $h) {
                return [
                    'homeworkId'      => $h->homework_id,
                    'courseId'        => $h->course_id,
                    'courseName'      => $h->course->course_name ?? '',
                    'homeworkTitle'   => $h->homework_title,
                    'homeworkContent' => $h->homework_content,
                    'deadline'        => $h->deadline,
                    'createTime'      => $h->create_time,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * 待批阅作业列表（已提交但未评分）
     */
    public function homeworkSubmitted(int $userId): array
    {
        $courseIds = $this->getUserCourseIds($userId);

        if (empty($courseIds)) {
            return [];
        }

        $submits = HomeworkSubmit::where('user_id', $userId)
            ->whereNull('score')
            ->whereHas('homework', function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds);
            })
            ->with('homework.course')
            ->orderBy('submit_time', 'desc')
            ->get()
            ->map(function (HomeworkSubmit $s) {
                return [
                    'submitId'        => $s->submit_id,
                    'homeworkId'      => $s->homework_id,
                    'homeworkTitle'   => $s->homework->homework_title ?? '',
                    'courseId'        => $s->homework->course_id ?? null,
                    'courseName'      => $s->homework->course->course_name ?? '',
                    'submitContent'   => $s->submit_content,
                    'submitFile'      => $s->submit_file,
                    'submitTime'      => $s->submit_time,
                ];
            });

        return $submits->values()->toArray();
    }

    /**
     * 已批阅作业列表（已评分）
     */
    public function homeworkScored(int $userId): array
    {
        $courseIds = $this->getUserCourseIds($userId);

        if (empty($courseIds)) {
            return [];
        }

        $submits = HomeworkSubmit::where('user_id', $userId)
            ->whereNotNull('score')
            ->whereHas('homework', function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds);
            })
            ->with('homework.course')
            ->orderBy('submit_time', 'desc')
            ->get()
            ->map(function (HomeworkSubmit $s) {
                return [
                    'submitId'        => $s->submit_id,
                    'homeworkId'      => $s->homework_id,
                    'homeworkTitle'   => $s->homework->homework_title ?? '',
                    'courseId'        => $s->homework->course_id ?? null,
                    'courseName'      => $s->homework->course->course_name ?? '',
                    'submitContent'   => $s->submit_content,
                    'submitFile'      => $s->submit_file,
                    'submitTime'      => $s->submit_time,
                    'score'           => $s->score,
                    'remark'          => $s->remark,
                ];
            });

        return $submits->values()->toArray();
    }

    /**
     * 获取用户已报名的课程ID列表
     */
    private function getUserCourseIds(int $userId): array
    {
        return TrainSign::where('user_id', $userId)
            ->where('status', 1)
            ->pluck('course_id')
            ->toArray();
    }

    /**
     * 我的作业列表
     *
     * 只查询已通过审核课程下的作业，可按课程筛选，按创建时间倒序
     */
    public function homeworkList(int $userId, int $page = 1, int $size = 10, ?int $courseId = null): array
    {
        $approvedCourseIds = TrainSign::where('user_id', $userId)
            ->where('status', 1)
            ->pluck('course_id');

        if ($approvedCourseIds->isEmpty()) {
            return ['total' => 0, 'list' => []];
        }

        $query = TrainHomework::whereIn('course_id', $approvedCourseIds)
            ->orderBy('create_time', 'desc');

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $total = $query->count();
        $list  = $query->forPage($page, $size)->get()->map(function (TrainHomework $homework) {
            return [
                'homeworkId'      => $homework->homework_id,
                'courseId'        => $homework->course_id,
                'homeworkTitle'   => $homework->homework_title,
                'homeworkContent' => $homework->homework_content,
                'deadline'        => $homework->deadline,
                'createTime'      => $homework->create_time,
            ];
        });

        return [
            'total' => $total,
            'list'  => $list->values(),
        ];
    }

    /**
     * 提交作业
     *
     * 校验作业存在 → 校验已通过课程审核 → 校验未重复提交 → 处理文件上传 → 写入提交记录
     */
    public function homeworkSubmit(int $userId, int $homeworkId, ?string $content = null, $file = null): array
    {
        /** @var TrainHomework $homework */
        $homework = TrainHomework::find($homeworkId);

        if (!$homework) {
            throw new BusinessException('作业不存在', ResponseCode::DATA_NOT_FOUND);
        }

        // 校验用户已报名该课程
        $approved = TrainSign::where('user_id', $userId)
            ->where('course_id', $homework->course_id)
            ->where('status', 1)
            ->exists();

        if (!$approved) {
            throw new BusinessException('您未通过该课程的报名审核，无法提交作业', ResponseCode::FORBIDDEN);
        }

        // 校验未重复提交
        $exists = HomeworkSubmit::where('user_id', $userId)
            ->where('homework_id', $homeworkId)
            ->exists();

        if ($exists) {
            throw new BusinessException('您已提交过该作业', ResponseCode::DUPLICATE_SUBMIT);
        }

        // 处理文件上传到 OSS
        $filePath = '';
        if ($file && $file->isValid()) {
            $ext    = $file->getClientOriginalExtension();
            $object = 'homework/' . uniqid() . '.' . $ext;

            try {
                $oss = new OssClient(
                    config('filesystems.disks.oss.access_id'),
                    config('filesystems.disks.oss.access_key'),
                    config('filesystems.disks.oss.endpoint'),
                );
                $oss->putObject(config('filesystems.disks.oss.bucket'), $object, $file->getContent());
            } catch (OssException | RequestCore_Exception $e) {
                \Illuminate\Support\Facades\Log::channel('exception')->error('OSS上传作业文件失败', [
                    'user_id' => $userId,
                    'object'  => $object,
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                ]);
                throw new BusinessException('作业文件上传失败，请稍后重试', ResponseCode::THIRD_PARTY_ERROR);
            }

            $filePath = 'https://' . config('filesystems.disks.oss.bucket') . '.'
                      . config('filesystems.disks.oss.endpoint') . '/' . $object;
        }

        $submit = HomeworkSubmit::create([
            'user_id'        => $userId,
            'homework_id'    => $homeworkId,
            'submit_content' => $content,
            'submit_file'    => $filePath,
            'submit_time'    => now(),
        ]);

        $this->logBusiness('用户提交作业', [
            'user_id'     => $userId,
            'homework_id' => $homeworkId,
            'submit_id'   => $submit->submit_id,
        ]);

        return [
            'submitId'   => $submit->submit_id,
            'submitFile' => $submit->submit_file,
            'submitTime' => $submit->submit_time,
        ];
    }

}
