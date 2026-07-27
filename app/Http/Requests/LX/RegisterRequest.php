<?php

namespace App\Http\Requests\LX;

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
            'password' => 'required|string|min:6|max:100',
            'email'    => 'required|email|max:50',
            'phone'    => 'required|string|size:11',
            'grade'    => 'required|string|max:50',
            'major'    => 'required|string|max:100',
            'code'     => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => '用户名不能为空',
            'username.max'      => '用户名不能超过50个字符',
            'password.required' => '密码不能为空',
            'password.min'      => '密码不能少于6个字符',
            'password.max'      => '密码不能超过100个字符',
            'email.required'    => '邮箱不能为空',
            'email.email'       => '邮箱格式不正确',
            'email.max'         => '邮箱不能超过50个字符',
            'phone.required'    => '手机号不能为空',
            'phone.size'        => '手机号必须为11位',
            'grade.required'    => '年级不能为空',
            'grade.max'         => '年级不能超过50个字符',
            'major.required'    => '专业不能为空',
            'major.max'         => '专业不能超过100个字符',
            'code.required'     => '验证码不能为空',
            'code.size'         => '验证码必须为6位',
        ];
    }
}
