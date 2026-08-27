<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\SysUser;
use App\Models\TrainSign;
use App\Models\HomeworkSubmit;
use App\Models\SignApplication;
use App\Models\CheckinRecord;
use App\Helpers\PhoneHelper;
use App\Models\SysAdmin;
use App\Traits\LogTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManageService
{
    use LogTrait;

    public function list(int $page = 1, int $size = 10, ?string $keyword = null, ?int $status = null): array
    {
        $labId = auth('admin_api')->user()->lab_id ?? 'software';

        // 学员
        $userQuery = SysUser::where('lab_id', $labId);
        if ($keyword) {
            $userQuery->where(function ($q) use ($keyword) {
                $q->where('real_name', 'like', "%{$keyword}%")
                  ->orWhere('student_id', 'like', "%{$keyword}%");
            });
        }
        if ($status !== null) $userQuery->where('status', $status);
        $users = $userQuery->get()->map(fn($u) => [
            'id'         => $u->user_id,
            'username'   => $u->username,
            'realName'   => $u->real_name,
            'role'       => 'student',
            'studentId'  => $u->student_id,
            'className'  => SignApplication::where('student_id', $u->student_id)->where('audit_status', 1)->value('group_name') ?? '',
            'college'    => $u->college,
            'major'      => $u->major,
            'grade'      => $u->grade,
            'phone'      => PhoneHelper::mask($u->phone ?? ''),
            'email'      => $u->email,
            'status'     => $u->status,
            'createTime' => $u->create_time,
            'sortTime'   => $u->create_time ?? '',
        ]);

        // 管理员
        $adminQuery = \App\Models\SysAdmin::where('lab_id', $labId);
        if ($keyword) {
            $adminQuery->where(function ($q) use ($keyword) {
                $q->where('real_name', 'like', "%{$keyword}%")
                  ->orWhere('admin_name', 'like', "%{$keyword}%");
            });
        }
        if ($status !== null) $adminQuery->where('status', $status);
        $admins = $adminQuery->get()->map(fn($a) => [
            'id'         => $a->admin_id,
            'username'   => $a->admin_name,
            'realName'   => $a->real_name,
            'role'       => 'admin',
            'studentId'  => null,
            'className'  => '',
            'college'    => null,
            'major'      => null,
            'grade'      => null,
            'phone'      => PhoneHelper::mask($a->phone ?? ''),
            'email'      => $a->email,
            'status'     => $a->status,
            'createTime' => $a->create_time ?? '',
            'sortTime'   => $a->create_time ?? '',
        ]);

        $merged = $users->concat($admins)->sortByDesc('sortTime')->values();
        $total = $merged->count();
        $list = $merged->forPage($page, $size)->map(fn($r) => array_diff_key($r, ['sortTime' => '']));

        return ['total' => $total, 'list' => $list->values()];
    }

    public function detail(int $userId, ?string $role = null): array
    {
        $labId = auth('admin_api')->user()->lab_id ?? 'software';

        // 优先按 role 参数查找；未传则先查学员表，再查管理员表
        if ($role === 'admin') {
            return $this->detailAdmin($userId, $labId);
        }
        if ($role === 'student') {
            return $this->detailStudent($userId, $labId);
        }

        $u = SysUser::find($userId);
        if ($u) {
            return $this->detailStudent($userId, $labId);
        }

        $a = SysAdmin::find($userId);
        if ($a) {
            return $this->detailAdmin($userId, $labId);
        }

        throw new BusinessException('账号不存在', ResponseCode::DATA_NOT_FOUND);
    }

    private function detailStudent(int $userId, string $labId): array
    {
        $u = SysUser::find($userId);
        if (!$u) throw new BusinessException('学员不存在', ResponseCode::DATA_NOT_FOUND);
        if ($u->lab_id !== $labId) throw new BusinessException('无权操作', ResponseCode::FORBIDDEN);

        $signs = TrainSign::with('course')->where('user_id', $userId)->orderBy('sign_time', 'desc')->get()->map(function ($s) {
            return ['signId' => $s->sign_id, 'courseName' => $s->course->course_name ?? '', 'status' => $s->status, 'signTime' => $s->sign_time];
        });

        $scores = HomeworkSubmit::with('homework.course')->where('user_id', $userId)->orderBy('submit_time', 'desc')->get()->map(function ($s) {
            return ['homeworkTitle' => $s->homework->homework_title ?? '', 'courseName' => $s->homework->course->course_name ?? '', 'submitTime' => $s->submit_time, 'score' => $s->score, 'remark' => $s->remark];
        });

        $app = SignApplication::where('student_id', $u->student_id)->where('audit_status', 1)->first();
        return [
            'userId'         => $u->user_id, 'username' => $u->username, 'realName' => $u->real_name,
            'role'           => 'student',
            'studentId'      => $u->student_id, 'className' => $app->group_name ?? '',
            'college'        => $u->college, 'major' => $u->major,
            'grade'          => $u->grade, 'phone' => $u->phone, 'email' => $u->email,
            'avatar'         => $u->avatar, 'status' => $u->status,
            'signList'       => $signs,
            'homeworkScores' => $scores,
            'createTime'     => $u->create_time,
        ];
    }

    private function detailAdmin(int $adminId, string $labId): array
    {
        $a = SysAdmin::find($adminId);
        if (!$a) throw new BusinessException('管理员不存在', ResponseCode::DATA_NOT_FOUND);
        if ($a->lab_id !== $labId) throw new BusinessException('无权操作', ResponseCode::FORBIDDEN);

        return [
            'userId'     => $a->admin_id,
            'username'   => $a->admin_name,
            'realName'   => $a->real_name,
            'role'       => 'admin',
            'studentId'  => null,
            'className'  => '',
            'college'    => null,
            'major'      => null,
            'grade'      => null,
            'phone'      => $a->phone,
            'email'      => $a->email,
            'avatar'     => null,
            'status'     => $a->status,
            'signList'       => [],
            'homeworkScores' => [],
            'createTime' => $a->create_time,
        ];
    }

    public function status(int $userId, int $status): array
    {
        $u = SysUser::find($userId);
        if (!$u) throw new BusinessException('学员不存在', ResponseCode::DATA_NOT_FOUND);
        if ($u->lab_id !== (auth('admin_api')->user()->lab_id ?? 'software')) throw new BusinessException('无权操作', ResponseCode::FORBIDDEN);
        $u->status = $status;
        $u->save();
        $this->logBusiness('管理员修改学员状态', ['user_id' => $userId, 'status' => $status]);
        return ['userId' => $u->user_id, 'status' => $status, 'statusText' => $status ? '已启用' : '已禁用'];
    }

    public function create(array $data): array
    {
        $isAdmin = ($data['role'] ?? 'student') === 'admin';
        $labId = auth('admin_api')->user()->lab_id ?? 'software';
        // 前端未传密码时回退默认密码
        $password = $data['password'] ?? 'Pass@123';

        try {
            return DB::transaction(function () use ($data, $isAdmin, $labId, $password) {
                if ($isAdmin) {
                    return $this->createAdmin($data, $labId, $password);
                }
                return $this->createStudent($data, $labId, $password);
            });
        } catch (QueryException $e) {
            $this->logException('管理员创建账号数据库异常', $e, [
                'username' => $data['username'] ?? '',
                'role'     => $isAdmin ? 'admin' : 'student',
            ]);
            throw new BusinessException(
                '账号创建失败：账号/学号/手机号可能已存在或字段不合法',
                ResponseCode::UNIQUE_CONFLICT
            );
        }
    }

    private function createAdmin(array $data, string $labId, string $password): array
    {
        if (SysAdmin::where('admin_name', $data['username'])->exists()) {
            throw new BusinessException('管理员账号已存在', ResponseCode::DATA_DUPLICATE);
        }

        $u = SysAdmin::create([
            'admin_name' => $data['username'],
            'password'   => Hash::make($password),
            'real_name'  => $data['realName'],
            'phone'      => $data['phone'] ?? null,
            'email'      => $data['email'] ?? null,
            'department' => $labId === 'ai' ? 2 : 1,
            'lab_id'     => $labId,
            'status'     => 1,
        ]);

        $this->logBusiness('管理员创建管理员账号', ['admin_id' => $u->admin_id, 'admin_name' => $u->admin_name]);
        return ['userId' => null, 'adminId' => $u->admin_id, 'username' => $u->admin_name, 'realName' => $u->real_name, 'role' => 'admin'];
    }

    private function createStudent(array $data, string $labId, string $password): array
    {
        if (SysUser::where('username', $data['username'])->exists()) {
            throw new BusinessException('学员账号已存在', ResponseCode::DATA_DUPLICATE);
        }

        $u = SysUser::create([
            'username'   => $data['username'],
            'password'   => Hash::make($password),
            'real_name'  => $data['realName'],
            'phone'      => $data['phone'] ?? null,
            'email'      => $data['email'] ?? null,
            'grade'      => $data['grade'] ?? null,
            'major'      => $data['major'] ?? null,
            'college'    => $data['college'] ?? null,
            'student_id' => $data['studentId'] ?? null,
            'lab_id'     => $labId,
            'status'     => 1,
        ]);

        // 有学号时：自动分班并写入培训名单（sign_application）
        if (!empty($data['studentId'])) {
            $this->syncTrainRoster($u, $data, $labId);
        }

        $this->logBusiness('管理员创建学员账号', ['user_id' => $u->user_id, 'username' => $u->username]);
        return ['userId' => $u->user_id, 'username' => $u->username, 'realName' => $u->real_name, 'role' => 'student'];
    }

    private function syncTrainRoster(SysUser $u, array $data, string $labId): void
    {
        // 分班（group_name）不由后端自动分配，统一由管理员在培训名单里导表（importClass）指定
        // 学校班级：前端可能发 className / class_name / grade 三种字段名，取其一写入 sign_application.class_name
        $className = $data['className'] ?? $data['class_name'] ?? $data['grade'] ?? null;

        $app = SignApplication::where('student_id', $u->student_id)->first();

        if (!$app) {
            SignApplication::create([
                'student_id'   => $u->student_id,
                'name'         => $u->real_name,
                'user_id'      => $u->user_id,
                'department'   => $labId === 'ai' ? 2 : 1,
                'college'      => $u->college,
                'major'        => $u->major,
                'class_name'   => $className,
                'phone'        => $u->phone,
                'status'       => 1,
                'audit_status' => 1,
                'lab_id'       => $labId,
                'submit_time'  => now(),
                'audit_time'   => now(),
            ]);
        } else {
            $app->name         = $u->real_name ?: $app->name;
            $app->user_id      = $u->user_id;
            $app->college      = $u->college ?: $app->college;
            $app->major        = $u->major ?: $app->major;
            $app->class_name   = $className ?: $app->class_name;
            $app->phone        = $u->phone ?: $app->phone;
            $app->status       = 1;
            $app->audit_status = 1;
            $app->lab_id       = $labId;
            $app->save();
        }
    }

    public function delete(int $userId, ?string $role = null): void
    {
        $labId = auth('admin_api')->user()->lab_id ?? 'software';

        if ($role === 'admin') {
            $a = SysAdmin::find($userId);
            if (!$a) throw new BusinessException('管理员不存在', ResponseCode::DATA_NOT_FOUND);
            if ($a->lab_id !== $labId) throw new BusinessException('无权操作', ResponseCode::FORBIDDEN);
            $a->delete();
            $this->logBusiness('管理员删除管理员账号', ['admin_id' => $userId]);
            return;
        }

        $u = SysUser::find($userId);
        if (!$u) throw new BusinessException('学员不存在', ResponseCode::DATA_NOT_FOUND);
        if ($u->lab_id !== $labId) throw new BusinessException('无权操作', ResponseCode::FORBIDDEN);

        DB::transaction(function () use ($u) {
            // 级联删除关联业务数据，避免其它页面残留该学员
            TrainSign::where('user_id', $u->user_id)->delete();
            HomeworkSubmit::where('user_id', $u->user_id)->delete();
            CheckinRecord::where('user_id', $u->user_id)->delete();

            // 培训名单（sign_application）同时按 user_id 与 student_id 清理
            $appQuery = SignApplication::where('user_id', $u->user_id);
            if (!empty($u->student_id)) {
                $appQuery->orWhere('student_id', $u->student_id);
            }
            $appQuery->delete();

            $u->delete();
        });

        $this->logBusiness('管理员删除学员', ['user_id' => $userId]);
    }
}
