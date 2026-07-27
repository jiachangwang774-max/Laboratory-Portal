<?php
// 重置密码请求
namespace App\Http\Requests\LX;

use App\Models\SysUser;
use App\Rules\PasswordStrength;
use Illuminate\Foundation\Http\FormRequest;

class ResetPwdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 根据邮箱查找用户，用于密码关联信息检测
        $email = $this->input('email', '');
        $user  = SysUser::where('email', $email)->first();

        return [
            'email'  => 'required|email|max:50',
            'code'   => 'required|string|size:6',
            'newPwd' => [
                'required',
                'string',
                new PasswordStrength([
                    'username' => $user?->username ?? '',
                    'phone'    => $user?->phone ?? '',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'  => '邮箱不能为空',
            'email.email'     => '邮箱格式不正确',
            'email.max'       => '邮箱不能超过50个字符',
            'code.required'   => '验证码不能为空',
            'code.size'       => '验证码必须为6位',
            'newPwd.required' => '新密码不能为空',
        ];
    }
}
