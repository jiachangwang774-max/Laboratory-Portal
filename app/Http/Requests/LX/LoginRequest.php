<?php
// 登录请求
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
            'student_id' => 'required|string|max:50',
            'password'   => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => '学号不能为空',
            'student_id.max'      => '学号不能超过50个字符',
            'password.required'   => '密码不能为空',
        ];
    }
}
