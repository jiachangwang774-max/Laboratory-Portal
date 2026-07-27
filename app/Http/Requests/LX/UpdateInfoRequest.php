<?php

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
            'realName' => 'nullable|string|max:20',
            'avatar'   => 'nullable|string|max:255',
            'email'    => 'nullable|email|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'realName.max' => '真实姓名不能超过20个字符',
            'avatar.max'   => '头像地址不能超过255个字符',
            'email.email'  => '邮箱格式不正确',
            'email.max'    => '邮箱不能超过50个字符',
        ];
    }
}
