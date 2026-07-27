<?php

namespace App\Http\Requests\LX;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:50',
            'code'  => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => '邮箱不能为空',
            'email.email'    => '邮箱格式不正确',
            'email.max'      => '邮箱不能超过50个字符',
            'code.required'  => '验证码不能为空',
            'code.size'      => '验证码必须为6位',
        ];
    }
}
