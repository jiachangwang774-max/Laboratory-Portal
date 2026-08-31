<?php

namespace App\Services\WJC;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\SignApplication;
use App\Models\SysUser;
use App\Models\TrainCourse;
use App\Models\TrainSign;
use App\Traits\LogTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SignAuditService
{
    use LogTrait;

    /**
     * 报名申请分页列表（仅已提交 status=1）
     */
    public function list(int $page = 1, int $size = 10, ?int $auditStatus = null, ?string $college = null, ?string $major = null): array
    {
        $labId = auth('admin_api')->user()->lab_id ?? 'software';
        $query = SignApplication::where('status', 1)
            ->where('lab_id', $labId)
            ->orderBy('submit_time', 'desc');

        if ($auditStatus !== null) {
            $query->where('audit_status', $auditStatus);
        }
        if ($college) {
            $query->where('college', $college);
        }
        if ($major) {
            $query->where('major', $major);
        }

        $total = $query->count();
        $list = $query->forPage($page, $size)->get()->map(function (SignApplication $app) {
            return [
                'id'              => $app->id,
                'name'            => $app->name,
                'studentId'       => $app->student_id,
                'department'      => $app->department,
                'college'         => $app->college,
                'major'           => $app->major,
                'className'       => $app->class_name,
                'phone'           => $app->phone,
                'auditStatus'     => $app->audit_status,
                'groupName'       => $app->group_name,
                'auditRemark'     => $app->audit_remark,
                'auditTime'       => $app->audit_time,
                'submitTime'      => $app->submit_time,
            ];
        });

        return ['total' => $total, 'list' => $list->values()];
    }

    /**
     * 报名申请详情
     */
    public function detail(int $id): array
    {
        $app = SignApplication::where('status', 1)->find($id);

        if (!$app) {
            throw new BusinessException('报名申请不存在', ResponseCode::DATA_NOT_FOUND);
        }

        return [
            'id'               => $app->id,
            'name'             => $app->name,
            'studentId'        => $app->student_id,
            'department'       => $app->department,
            'departmentText'   => $app->department == 1 ? '软件开发实验室' : '人工智能实验室',
            'college'          => $app->college,
            'major'            => $app->major,
            'className'        => $app->class_name,
            'phone'            => $app->phone,
            'selfIntroduction' => $app->self_introduction,
            'auditStatus'      => $app->audit_status,
            'groupName'        => $app->group_name,
            'auditRemark'      => $app->audit_remark,
            'auditAdmin'       => $app->admin->real_name ?? '',
            'auditTime'        => $app->audit_time,
            'submitTime'       => $app->submit_time,
        ];
    }

    /**
     * 审核通过
     */
    public function approve(int $id): array
    {
        $app = SignApplication::where('status', 1)->find($id);

        if (!$app) {
            throw new BusinessException('报名申请不存在', ResponseCode::DATA_NOT_FOUND);
        }

        if ($app->audit_status === 1) {
            throw new BusinessException('该申请已审核通过', ResponseCode::DUPLICATE_SUBMIT);
        }

        $adminId = auth('admin_api')->user()->admin_id;

        // 分班缺失时均衡分班到一班/二班/三班
        if (empty($app->group_name)) {
            $app->group_name = $this->assignGroup();
        }

        // 用事务包裹 sign_application + sys_user + train_sign，保证要么都写要么都不写
        return DB::transaction(function () use ($app, $adminId, $id) {
            $app->audit_status = 1;
            $app->audit_admin  = $adminId;
            $app->audit_time   = now();
            $app->save();

            // 审核通过后：自动创建/更新学员账号，并回填 user_id
            $this->syncStudentAccount($app);

            // 为该学生创建课程报名记录（分班）
            $this->syncTrainSign($app);

            $this->logBusiness('管理员审核通过报名申请', [
                'admin_id'       => $adminId,
                'student_id'     => $app->student_id,
                'application_id' => $id,
            ]);

            return [
                'id'          => $app->id,
                'auditStatus' => $app->audit_status,
                'auditTime'   => $app->audit_time,
            ];
        });
    }

    /**
     * 按顺序分班：一班→二班→三班 轮流
     */
    private function assignGroup(): string
    {
        $groups = ['一班', '二班', '三班'];
        $last = SignApplication::where('audit_status', 1)
            ->whereNotNull('group_name')
            ->orderBy('audit_time', 'desc')
            ->value('group_name');
        $idx = $last ? (array_search($last, $groups) + 1) % 3 : 0;
        return $groups[$idx];
    }

    /**
     * 审核通过后自动创建/更新学员账号，并回填 user_id
     */
    private function syncStudentAccount(SignApplication $app): void
    {
        $studentId = $app->student_id ?: null;

        if (empty($studentId)) {
            throw new BusinessException(
                '审核失败：报名记录缺少学号，无法创建学员账号',
                ResponseCode::BUSINESS_ERROR
            );
        }

        try {
            $user = SysUser::where('student_id', $studentId)->first();

            if (!$user) {
                $user = SysUser::create([
                    'username'   => $studentId,
                    'password'   => Hash::make('Pass@123'),
                    'real_name'  => $app->name ?: $studentId,
                    'student_id' => $studentId,
                    'college'    => $app->college,
                    'major'      => $app->major,
                    'lab_id'     => $app->lab_id ?: 'software',
                    'status'     => 1,
                ]);
            } else {
                $user->real_name = $app->name ?: $user->real_name;
                $user->college   = $app->college ?: $user->college;
                $user->major     = $app->major ?: $user->major;
                $user->lab_id    = $app->lab_id ?: $user->lab_id;
                $user->status    = 1;
                $user->save();
            }

            $app->user_id = $user->user_id;
            $app->save();
        } catch (QueryException $e) {
            $this->logException('审核通过写入学员账号失败', $e, [
                'student_id'     => $studentId,
                'application_id' => $app->id,
                'sql_state'      => $e->getCode(),
            ]);
            throw new BusinessException(
                '审核失败：学员账号写入异常（学号或账号可能已存在），已回滚',
                ResponseCode::UNIQUE_CONFLICT
            );
        }
    }

    /**
     * 同步创建 train_sign 报名记录
     *
     * 找到与学生分配班级匹配的活跃课程（由该班级的管理员发布），创建课程报名记录
     */
    private function syncTrainSign(SignApplication $app): void
    {
        if (empty($app->group_name)) {
            return;
        }

        // 查找与分班名称匹配的活跃课程（管理员发布课程时指定了班级）
        $courses = TrainCourse::enabled()
            ->where('group_name', $app->group_name)
            ->latest('create_time')
            ->get();

        foreach ($courses as $course) {
            // 避免重复创建
            $exists = TrainSign::where('user_id', $app->user_id)
                ->where('course_id', $course->course_id)
                ->exists();

            if (!$exists) {
                TrainSign::create([
                    'user_id'    => $app->user_id,
                    'course_id'  => $course->course_id,
                    'group_name' => $app->group_name,
                    'status'     => 1,
                    'sign_time'  => now(),
                ]);
            }
        }
    }

    /**
     * 重新分班：按审核时间顺序 一班→二班→三班 重新分配
     */
    public function regroup(): array
    {
        $apps = SignApplication::where('audit_status', 1)->orderBy('audit_time')->get();
        $groups = ['一班', '二班', '三班'];
        $result = [];
        foreach ($apps as $i => $app) {
            $g = $groups[$i % 3];
            $app->group_name = $g;
            $app->save();
            $result[] = ['id' => $app->id, 'name' => $app->name, 'groupName' => $g];
        }
        $this->logBusiness('管理员重新分班', ['total' => count($apps)]);
        return ['count' => count($apps), 'groups' => 3, 'list' => $result];
    }

    /**
     * 审核驳回
     */
    public function reject(int $id, ?string $remark = null): array
    {
        $app = SignApplication::where('status', 1)->find($id);

        if (!$app) {
            throw new BusinessException('报名申请不存在', ResponseCode::DATA_NOT_FOUND);
        }

        if ($app->audit_status === 2) {
            throw new BusinessException('该申请已被驳回', ResponseCode::DUPLICATE_SUBMIT);
        }

        $adminId = auth('admin_api')->user()->admin_id;

        $app->audit_status = 2;
        $app->audit_admin  = $adminId;
        $app->audit_remark = $remark;
        $app->audit_time   = now();
        $app->save();

        $this->logBusiness('管理员驳回报名申请', [
            'admin_id'    => $adminId,
            'student_id'  => $app->student_id,
            'application_id' => $id,
            'remark'      => $remark,
        ]);

        return [
            'id'          => $app->id,
            'auditStatus' => $app->audit_status,
            'auditRemark' => $app->audit_remark,
            'auditTime'   => $app->audit_time,
        ];
    }

    /**
     * 获取指定班级的学生列表
     */
    public function classList(string $groupName): array
    {
        $labId = auth('admin_api')->user()->lab_id ?? 'software';
        $list = SignApplication::where('status', 1)
            ->where('audit_status', 1)
            ->where('lab_id', $labId)
            ->where('group_name', $groupName)
            ->orderBy('student_id')
            ->get()
            ->map(function (SignApplication $app) {
                return [
                    'id'         => $app->id,
                    'name'       => $app->name,
                    'studentId'  => $app->student_id,
                    'college'    => $app->college,
                    'major'      => $app->major,
                    'className'  => $app->class_name,
                    'phone'      => $app->phone,
                    'groupName'  => $app->group_name,
                    'auditTime'  => $app->audit_time,
                ];
            });

        return ['groupName' => $groupName, 'count' => $list->count(), 'list' => $list->values()];
    }

    /**
     * 导出指定班级的学生信息
     */
    public function classExport(string $groupName): array
    {
        $data = $this->classList($groupName);
        return $data['list']->map(function ($r, $i) {
            return [
                '序号'   => $i + 1,
                '姓名'   => $r['name'],
                '学号'   => $r['studentId'],
                '学院'   => $r['college'],
                '专业'   => $r['major'],
                '班级'   => $r['className'],
                '分班'   => $r['groupName'],
                '手机号' => $r['phone'],
            ];
        })->toArray();
    }

    /**
     * 导入分班Excel：按学号匹配更新group_name
     */
    public function importClass($filePath): array
    {
        $zip = new \ZipArchive();
        $zip->open($filePath);
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        // 解析共享字符串
        $strings = [];
        if ($xml) {
            $sx = simplexml_load_string($xml);
            foreach ($sx->si as $si) {
                $t = '';
                foreach ($si->t ?? [] as $part) $t .= (string) $part;
                if ($t === '') $t = (string) ($si->t ?? '');
                $strings[] = $t;
            }
        }

        // 解析sheet
        $sx = simplexml_load_string($sheet);
        $nsPrefix = '';
        foreach ($sx->getNamespaces(true) as $p => $u) {
            if (str_contains($u, 'spreadsheetml')) { $nsPrefix = $p ? "$p:" : ''; break; }
        }
        if (!$nsPrefix) $nsPrefix = '';

        $rows = [];
        foreach ($sx->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $r = (string) $c['r'];
                $col = preg_replace('/\d/', '', $r);
                $val = (string) ($c->v ?? '');
                if ((string) ($c['t'] ?? '') === 's' && isset($strings[(int) $val])) {
                    $val = $strings[(int) $val];
                }
                $cells[$col] = trim($val);
            }
            if (!empty($cells)) $rows[] = $cells;
        }

        // 跳过表头（第一行）
        $dataRows = count($rows) > 1 ? array_slice($rows, 1) : [];

        $success = 0; $fail = 0;
        $labId = auth('admin_api')->user()->lab_id ?? 'software';
        $validGroups = ['一班', '二班', '三班'];

        foreach ($dataRows as $r) {
            $studentId = $r['C'] ?? $r['B'] ?? '';  // C=学号
            $groupName = $r['G'] ?? $r['F'] ?? '';  // G=分班

            if (empty($studentId) || empty($groupName)) { $fail++; continue; }
            if (!in_array($groupName, $validGroups)) { $fail++; continue; }

            $app = SignApplication::where('lab_id', $labId)
                ->where('audit_status', 1)
                ->where('student_id', $studentId)
                ->first();

            if ($app) {
                $app->group_name = $groupName;
                $app->save();
                $success++;
            } else {
                $name = $r['A'] ?? '';
                $app = SignApplication::where('lab_id', $labId)->where('audit_status', 1)->where('name', $name)->first();
                if ($app) { $app->group_name = $groupName; $app->save(); $success++; }
                else { $fail++; }
            }
        }

        $this->logBusiness('管理员导入分班', ['success' => $success, 'fail' => $fail]);
        return ['successCount' => $success, 'failCount' => $fail];
    }
}
