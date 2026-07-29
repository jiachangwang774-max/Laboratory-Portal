<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class UserCreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'username'  => 'required|string|max:50|unique:sys_user,username',
            'realName'  => 'required|string|max:20',
            'phone'     => 'nullable|string|max:11',
            'email'     => 'nullable|email|max:50',
            'grade'     => 'nullable|string|max:20',
            'major'     => 'nullable|string|max:50',
            'college'   => 'nullable|string|max:50',
            'studentId' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => '登录账号不能为空',
            'username.unique'   => '该账号已存在',
            'realName.required' => '真实姓名不能为空',
        ];
    }
}
