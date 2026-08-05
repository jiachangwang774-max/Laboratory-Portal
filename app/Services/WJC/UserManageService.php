<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\SysUser;
use App\Models\TrainSign;
use App\Models\HomeworkSubmit;
use App\Models\SignApplication;
use App\Helpers\PhoneHelper;
use App\Traits\LogTrait;
use Illuminate\Support\Facades\Hash;

class UserManageService
{
    use LogTrait;

    public function list(int $page = 1, int $size = 10, ?string $keyword = null, ?int $status = null): array
    {
        $query = SysUser::orderBy('create_time', 'desc');
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('real_name', 'like', "%{$keyword}%")
                  ->orWhere('student_id', 'like', "%{$keyword}%");
            });
        }
        if ($status !== null) $query->where('status', $status);

        $total = $query->count();
        $list = $query->forPage($page, $size)->get()->map(function (SysUser $u) {
            $app = SignApplication::where('user_id', $u->user_id)->where('audit_status', 1)->first();
            return [
                'userId'    => $u->user_id,
                'username'  => $u->username,
                'realName'  => $u->real_name,
                'studentId' => $u->student_id,
                'className' => $app->group_name ?? '',
                'college'   => $u->college,
                'major'     => $u->major,
                'grade'     => $u->grade,
                'phone'     => PhoneHelper::mask($u->phone ?? ''),
                'email'     => $u->email,
                'status'    => $u->status,
                'createTime'=> $u->create_time,
            ];
        });

        return ['total' => $total, 'list' => $list->values()];
    }

    public function detail(int $userId): array
    {
        $u = SysUser::find($userId);
        if (!$u) throw new BusinessException('学员不存在', ResponseCode::DATA_NOT_FOUND);

        $signs = TrainSign::with('course')->where('user_id', $userId)->orderBy('sign_time', 'desc')->get()->map(function ($s) {
            return ['signId' => $s->sign_id, 'courseName' => $s->course->course_name ?? '', 'status' => $s->status, 'signTime' => $s->sign_time];
        });

        $scores = HomeworkSubmit::with('homework.course')->where('user_id', $userId)->orderBy('submit_time', 'desc')->get()->map(function ($s) {
            return ['homeworkTitle' => $s->homework->homework_title ?? '', 'courseName' => $s->homework->course->course_name ?? '', 'submitTime' => $s->submit_time, 'score' => $s->score, 'remark' => $s->remark];
        });

        $app = SignApplication::where('user_id', $userId)->where('audit_status', 1)->first();
        return [
            'userId'         => $u->user_id, 'username' => $u->username, 'realName' => $u->real_name,
            'studentId'      => $u->student_id, 'className' => $app->group_name ?? '',
            'college'        => $u->college, 'major' => $u->major,
            'grade'          => $u->grade, 'phone' => $u->phone, 'email' => $u->email,
            'avatar'         => $u->avatar, 'status' => $u->status,
            'signList'       => $signs,
            'homeworkScores' => $scores,
            'createTime'     => $u->create_time,
        ];
    }

    public function status(int $userId, int $status): array
    {
        $u = SysUser::find($userId);
        if (!$u) throw new BusinessException('学员不存在', ResponseCode::DATA_NOT_FOUND);
        $u->status = $status;
        $u->save();
        $this->logBusiness('管理员修改学员状态', ['user_id' => $userId, 'status' => $status]);
        return ['userId' => $u->user_id, 'status' => $status, 'statusText' => $status ? '已启用' : '已禁用'];
    }

    public function create(array $data): array
    {
        $isAdmin = ($data['role'] ?? 'student') === 'admin';

        if ($isAdmin) {
            $u = \App\Models\SysAdmin::create([
                'admin_name' => $data['username'],
                'password'   => Hash::make('Pass@123'),
                'real_name'  => $data['realName'],
                'phone'      => $data['phone'] ?? null,
                'email'      => $data['email'] ?? null,
                'department' => 1,
                'status'     => 1,
            ]);
            $this->logBusiness('管理员创建管理员账号', ['admin_id' => $u->admin_id, 'admin_name' => $u->admin_name]);
            return ['userId' => null, 'adminId' => $u->admin_id, 'username' => $u->admin_name, 'realName' => $u->real_name, 'role' => 'admin'];
        }

        $u = SysUser::create([
            'username'   => $data['username'],
            'password'   => Hash::make('Pass@123'),
            'real_name'  => $data['realName'],
            'phone'      => $data['phone'] ?? null,
            'email'      => $data['email'] ?? null,
            'grade'      => $data['grade'] ?? null,
            'major'      => $data['major'] ?? null,
            'college'    => $data['college'] ?? null,
            'student_id' => $data['studentId'] ?? null,
            'status'     => 1,
        ]);

        $this->logBusiness('管理员创建学员账号', ['user_id' => $u->user_id, 'username' => $u->username]);
        return ['userId' => $u->user_id, 'username' => $u->username, 'realName' => $u->real_name, 'role' => 'student'];
    }

    public function delete(int $userId): void
    {
        $u = SysUser::find($userId);
        if (!$u) throw new BusinessException('学员不存在', ResponseCode::DATA_NOT_FOUND);
        $u->delete();
        $this->logBusiness('管理员删除学员', ['user_id' => $userId]);
    }
}
