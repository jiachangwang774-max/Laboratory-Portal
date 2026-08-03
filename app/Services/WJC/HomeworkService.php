<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\HomeworkSubmit;
use App\Models\SignApplication;
use App\Models\TrainHomework;
use App\Models\TrainSign;
use App\Traits\LogTrait;

class HomeworkService
{
    use LogTrait;

    public function create(int $adminId, int $courseId, string $title, ?string $content, ?string $deadline): array
    {
        $hw = TrainHomework::create([
            'course_id'        => $courseId,
            'homework_title'   => $title,
            'homework_content' => $content,
            'deadline'         => $deadline,
            'create_admin'     => $adminId,
            'create_time'      => now(),
        ]);
        $this->logBusiness('管理员布置作业', ['admin_id' => $adminId, 'homework_id' => $hw->homework_id]);
        return ['homeworkId' => $hw->homework_id, 'homeworkTitle' => $hw->homework_title];
    }

    public function update(int $homeworkId, array $data): array
    {
        $hw = TrainHomework::find($homeworkId);
        if (!$hw) throw new BusinessException('作业不存在', ResponseCode::DATA_NOT_FOUND);

        if (isset($data['homeworkTitle'])) $hw->homework_title = $data['homeworkTitle'];
        if (isset($data['homeworkContent'])) $hw->homework_content = $data['homeworkContent'];
        if (isset($data['deadline'])) $hw->deadline = $data['deadline'];
        $hw->save();

        $this->logBusiness('管理员编辑作业', ['homework_id' => $homeworkId]);
        return ['homeworkId' => $hw->homework_id, 'homeworkTitle' => $hw->homework_title];
    }

    public function delete(int $homeworkId): void
    {
        $hw = TrainHomework::find($homeworkId);
        if (!$hw) throw new BusinessException('作业不存在', ResponseCode::DATA_NOT_FOUND);
        $hw->delete();
        $this->logBusiness('管理员删除作业', ['homework_id' => $homeworkId]);
    }

    public function list(int $page = 1, int $size = 10, ?int $courseId = null): array
    {
        $query = TrainHomework::with('course')->orderBy('create_time', 'desc');
        if ($courseId) $query->where('course_id', $courseId);

        $total = $query->count();
        $list = $query->forPage($page, $size)->get()->map(function (TrainHomework $hw) {
            return [
                'homeworkId'      => $hw->homework_id,
                'courseId'        => $hw->course_id,
                'courseName'      => $hw->course->course_name ?? '',
                'homeworkTitle'   => $hw->homework_title,
                'deadline'        => $hw->deadline,
                'submitCount'     => HomeworkSubmit::where('homework_id', $hw->homework_id)->count(),
                'createTime'      => $hw->create_time,
            ];
        });

        return ['total' => $total, 'list' => $list->values()];
    }

    // ===== 批改 =====

    public function submitList(int $page = 1, int $size = 10, ?int $homeworkId = null, ?int $courseId = null): array
    {
        $query = HomeworkSubmit::with(['user', 'homework.course'])->orderBy('submit_time', 'desc');
        if ($homeworkId) $query->where('homework_id', $homeworkId);
        if ($courseId) {
            $homeworkIds = TrainHomework::where('course_id', $courseId)->pluck('homework_id');
            $query->whereIn('homework_id', $homeworkIds);
        }

        $total = $query->count();
        $list = $query->forPage($page, $size)->get()->map(function (HomeworkSubmit $s) {
            $app = SignApplication::where('user_id', $s->user_id)->where('audit_status', 1)->first();
            return [
                'submitId'      => $s->submit_id,
                'userId'        => $s->user_id,
                'realName'      => $s->user->real_name ?? '',
                'studentId'     => $app->student_id ?? '',
                'className'     => $app->group_name ?? '',
                'homeworkId'    => $s->homework_id,
                'homeworkTitle' => $s->homework->homework_title ?? '',
                'courseName'    => $s->homework->course->course_name ?? '',
                'submitTime'    => $s->submit_time,
                'score'         => $s->score,
                'statusText'    => $s->score !== null ? '已批改' : '待批改',
            ];
        });

        return ['total' => $total, 'list' => $list->values()];
    }

    public function submitDetail(int $submitId): array
    {
        $s = HomeworkSubmit::with(['user', 'homework.course'])->find($submitId);
        if (!$s) throw new BusinessException('提交记录不存在', ResponseCode::DATA_NOT_FOUND);

        $app = SignApplication::where('user_id', $s->user_id)->where('audit_status', 1)->first();
        return [
            'submitId'      => $s->submit_id,
            'userRealName'  => $s->user->real_name ?? '',
            'userPhone'     => $s->user->phone ?? '',
            'studentId'     => $app->student_id ?? '',
            'className'     => $app->group_name ?? '',
            'homeworkTitle' => $s->homework->homework_title ?? '',
            'courseName'    => $s->homework->course->course_name ?? '',
            'submitContent' => $s->submit_content,
            'submitFile'    => $s->submit_file,
            'submitTime'    => $s->submit_time,
            'score'         => $s->score,
            'remark'        => $s->remark,
        ];
    }

    public function deleteSubmit(int $submitId): void
    {
        $s = HomeworkSubmit::find($submitId);
        if (!$s) throw new BusinessException('提交记录不存在', ResponseCode::DATA_NOT_FOUND);
        $s->delete();
        $this->logBusiness('管理员删除作业提交', ['submit_id' => $submitId]);
    }

    public function score(int $submitId, int $score, ?string $remark): array
    {
        $s = HomeworkSubmit::find($submitId);
        if (!$s) throw new BusinessException('提交记录不存在', ResponseCode::DATA_NOT_FOUND);

        $s->score = $score;
        $s->remark = $remark;
        $s->save();

        $this->logBusiness('管理员批改作业', ['submit_id' => $submitId, 'score' => $score]);
        return ['submitId' => $s->submit_id, 'score' => $score, 'remark' => $remark];
    }
}
