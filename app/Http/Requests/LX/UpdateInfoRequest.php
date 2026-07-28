<?php
// 更新用户信息请求
namespace App\Http\Requests\LX;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'realName'   => 'nullable|string|max:20',
            'avatar'     => 'nullable|string|max:255',
            'email'      => 'nullable|email|max:50',
            'phone'      => 'nullable|string|max:11',
            'grade'      => 'nullable|string|max:50',
            'major'      => 'nullable|string|max:100',
            'college'    => 'nullable|string|max:100',
            'student_id' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'realName.max'   => '真实姓名不能超过20个字符',
            'avatar.max'     => '头像地址不能超过255个字符',
            'email.email'    => '邮箱格式不正确',
            'email.max'      => '邮箱不能超过50个字符',
            'phone.max'      => '手机号不能超过11个字符',
            'grade.max'      => '年级不能超过50个字符',
            'major.max'      => '专业不能超过100个字符',
            'college.max'    => '学院不能超过100个字符',
            'student_id.max' => '学号不能超过50个字符',
        ];
    }
}
