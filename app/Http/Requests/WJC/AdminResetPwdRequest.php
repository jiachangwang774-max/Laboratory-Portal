<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\PasswordStrength;

class AdminResetPwdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'  => 'required|email|max:50',
            'code'   => 'required|string|max:10',
            'newPwd' => ['required', 'string', new PasswordStrength()],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'  => '邮箱不能为空',
            'email.email'     => '邮箱格式不正确',
            'code.required'   => '验证码不能为空',
            'code.max'        => '验证码不能超过10位',
            'newPwd.required' => '新密码不能为空',
        ];
    }
}
