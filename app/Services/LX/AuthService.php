<?php

namespace App\Services\LX;

use App\Enums\ResponseCode;
use App\Enums\VerifyCodeType;
use App\Exceptions\BusinessException;
use App\Helpers\PhoneHelper;
use App\Models\SysPasswordHistory;
use App\Models\SysUser;
use App\Models\VerifyCode;
use App\Traits\LogTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use OSS\OssClient;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthService
{
    use LogTrait;

    /**
     * 用户登录
     *
     * 按学号查找用户 → 校验密码 → 检查账号状态 → 签发 accessToken
     */
    public function login(string $studentId, string $password): array
    {
        $user = SysUser::where('student_id', $studentId)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $this->logLogin('用户登录', 0, $studentId, 0, '学号或密码错误');
            throw new BusinessException('学号或密码错误', ResponseCode::PASSWORD_ERROR);
        }

        if ($user->status !== 1) {
            $this->logLogin('用户登录', $user->user_id, $user->username, 0, '账号已被禁用');
            throw new BusinessException('账号已被禁用，请联系管理员', ResponseCode::ACCOUNT_DISABLED);
        }

        try {
            $accessToken = auth('user_api')->login($user);
        } catch (JWTException $e) {
            $this->logException('JWT 令牌签发异常', $e, ['studentId' => $studentId]);
            throw new BusinessException('登录失败，请稍后重试', ResponseCode::SYSTEM_ERROR);
        }

        $this->logLogin('用户登录', $user->user_id, $user->username, 1);

        return [
            'accessToken'  => $accessToken,
            'userInfo'     => $this->formatUser($user),
        ];
    }

    /**
     * 用户登出
     *
     * 将当前 accessToken 加入黑名单
     */
    public function logout(): void
    {
        /** @var SysUser|null $user */
        $user = auth('user_api')->user();

        if ($user) {
            $this->logBusiness('用户登出', ['user_id' => $user->user_id]);
        }

        auth('user_api')->logout();
    }

    /**
     * 获取当前登录用户信息
     */
    public function info(): array
    {
        /** @var SysUser $user */
        $user = auth('user_api')->user();

        return $this->formatUser($user);
    }

    /**
     * 修改个人资料
     *
     * 仅更新传入的非空字段，记录变更前后快照
     */
    public function updateInfo(array $data): array
    {
        /** @var SysUser $user */
        $user = auth('user_api')->user();

        $updateData = [];
        if (isset($data['realName'])) {
            $updateData['real_name'] = $data['realName'];
        }
        if (isset($data['avatar'])) {
            $updateData['avatar'] = $data['avatar'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (isset($data['phone'])) {
            $updateData['phone'] = $data['phone'];
        }
        if (isset($data['grade'])) {
            $updateData['grade'] = $data['grade'];
        }
        if (isset($data['major'])) {
            $updateData['major'] = $data['major'];
        }
        if (isset($data['college'])) {
            $updateData['college'] = $data['college'];
        }
        if (isset($data['student_id'])) {
            $updateData['student_id'] = $data['student_id'];
        }

        if (empty($updateData)) {
            return $this->formatUser($user);
        }

        $before = $user->only(array_keys($updateData));
        $user->update($updateData);
        $user->refresh();

        $this->logAudit(
            '用户修改个人资料',
            $before,
            $user->only(array_keys($updateData)),
            ['user_id' => $user->user_id]
        );

        return $this->formatUser($user);
    }

    /**
     * 上传头像到 OSS 并自动更新用户 avatar 字段
     */
    public function uploadAvatar(UploadedFile $file): array
    {
        /** @var SysUser $user */
        $user = auth('user_api')->user();

        // 生成唯一文件名
        $extension = $file->getClientOriginalExtension();
        $object    = 'avatars/' . uniqid() . '.' . $extension;

        // 上传到 OSS
        $ossClient = new OssClient(
            config('filesystems.disks.oss.access_id'),
            config('filesystems.disks.oss.access_key'),
            config('filesystems.disks.oss.endpoint'),
        );
        $ossClient->putObject(
            config('filesystems.disks.oss.bucket'),
            $object,
            $file->getContent(),
        );

        // 拼接公开访问 URL
        $url = 'https://' . config('filesystems.disks.oss.bucket') . '.'
             . config('filesystems.disks.oss.endpoint') . '/' . $object;

        $this->logBusiness('头像上传成功', [
            'user_id' => $user->user_id,
            'object'  => $object,
            'url'     => $url,
        ]);

        // 自动更新用户头像字段
        $user->avatar = $url;
        $user->save();

        return [
            'avatar' => $url,
        ];
    }

    /**
     * 修改密码（通过邮箱验证码）
     *
     * 验证码校验 → 检查历史复用 → 更新为新密码 → 记录历史 → 删除已用验证码
     */
    public function updatePwd(string $code, string $newPwd): void
    {
        /** @var SysUser $user */
        $user = auth('user_api')->user();

        // 校验邮箱验证码
        $verifyCode = VerifyCode::where('target', $user->email)
            ->where('code', $code)
            ->where('type', VerifyCodeType::PWD_RESET->value)
            ->where('expire_time', '>', now())
            ->first();

        if (!$verifyCode) {
            throw new BusinessException('验证码错误或已过期', ResponseCode::VERIFY_CODE_ERROR);
        }

        // 检查新密码是否与历史密码重复
        $this->checkPasswordHistory($user->user_id, $newPwd);

        $newHash = Hash::make($newPwd);
        $user->password = $newHash;
        $user->save();

        // 记录密码历史
        $this->recordPasswordHistory($user->user_id, $newHash);

        // 删除已使用的验证码
        $verifyCode->delete();

        $this->logBusiness('用户修改密码', ['user_id' => $user->user_id]);
    }

    /**
     * 格式化用户信息输出
     */
    private function formatUser(SysUser $user): array
    {
        return [
            'userId'     => $user->user_id,
            'username'   => $user->username,
            'realName'   => $user->real_name,
            'avatar'     => $user->avatar,
            'phone'      => $user->phone ? PhoneHelper::mask($user->phone) : null,
            'email'      => $user->email,
            'grade'      => $user->grade,
            'major'      => $user->major,
            'college'    => $user->college,
            'student_id' => $user->student_id,
        ];
    }

    /**
     * 检查新密码是否与历史密码重复。
     *
     * 取最近 N 条历史记录逐一比对，命中则抛出异常。
     */
    private function checkPasswordHistory(int $userId, string $newPwd): void
    {
        $limit = config('password.history_limit', 5);

        $histories = SysPasswordHistory::where('user_id', $userId)
            ->orderBy('create_time', 'desc')
            ->limit($limit)
            ->get();

        foreach ($histories as $history) {
            if (Hash::check($newPwd, $history->password_hash)) {
                throw new BusinessException(
                    "新密码不能与近期使用过的 {$limit} 次密码相同",
                    ResponseCode::BUSINESS_ERROR,
                );
            }
        }
    }

    /**
     * 记录密码到历史表，并仅保留最近 N 条。
     */
    private function recordPasswordHistory(int $userId, string $hash): void
    {
        SysPasswordHistory::create([
            'user_id'       => $userId,
            'password_hash' => $hash,
            'create_time'   => now(),
        ]);

        // 仅保留最近 N 条，删除多余的旧记录
        $limit = config('password.history_limit', 5);

        $idsToKeep = SysPasswordHistory::where('user_id', $userId)
            ->orderBy('create_time', 'desc')
            ->limit($limit)
            ->pluck('id');

        SysPasswordHistory::where('user_id', $userId)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}
