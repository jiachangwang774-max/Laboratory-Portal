<?php

namespace App\Http\Requests\LX;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:50',
            'password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => '用户名不能为空',
            'username.max'      => '用户名不能超过50个字符',
            'password.required' => '密码不能为空',
        ];
    }
}
