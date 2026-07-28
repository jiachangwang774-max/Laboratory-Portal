<?php
// 注册请求
namespace App\Http\Requests\LX;

use App\Rules\PasswordStrength;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:50',
            'password' => [
                'required',
                'string',
                'confirmed',
                new PasswordStrength([
                    'username' => $this->input('username', ''),
                ]),
            ],
            'email'      => 'required|email|max:50',
            'grade'      => 'required|string|max:50',
            'major'      => 'required|string|max:100',
            'college'    => 'required|string|max:100',
            'student_id' => 'required|string|max:50',
            'code'       => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required'    => '用户名不能为空',
            'username.max'         => '用户名不能超过50个字符',
            'password.required'    => '密码不能为空',
            'password.confirmed'   => '两次输入的密码不一致',
            'email.required'       => '邮箱不能为空',
            'email.email'          => '邮箱格式不正确',
            'email.max'            => '邮箱不能超过50个字符',
            'grade.required'       => '年级不能为空',
            'grade.max'            => '年级不能超过50个字符',
            'major.required'       => '专业不能为空',
            'major.max'            => '专业不能超过100个字符',
            'college.required'     => '学院不能为空',
            'college.max'          => '学院不能超过100个字符',
            'student_id.required'  => '学号不能为空',
            'student_id.max'       => '学号不能超过50个字符',
            'code.required'        => '验证码不能为空',
            'code.size'            => '验证码必须为6位',
        ];
    }
}
