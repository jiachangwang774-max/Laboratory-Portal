<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\PasswordStrength;

class UserCreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $role = $this->input('role', 'student');

        return [
            'username'  => 'required|string|max:50',
            'realName'  => 'required|string|max:20',
            'phone'     => 'nullable|string|max:11',
            'email'     => 'nullable|email|max:50',
            'grade'     => 'nullable|string|max:20',
            'className'  => 'nullable|string|max:100',
            'class_name' => 'nullable|string|max:100',
            'major'     => 'nullable|string|max:50',
            'college'   => 'nullable|string|max:50',
            'studentId' => [Rule::requiredIf($role === 'student'), 'string', 'max:20'],
            'password'  => ['nullable', 'string', new PasswordStrength()],
            'role'      => 'nullable|string|in:student,admin',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required'  => '登录账号不能为空',
            'username.unique'    => '该账号已存在',
            'realName.required'  => '真实姓名不能为空',
            'studentId.required' => '学员账号必须填写学号，否则无法登录学生端',
        ];
    }
}
