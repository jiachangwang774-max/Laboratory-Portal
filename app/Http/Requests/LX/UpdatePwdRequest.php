<?php

namespace App\Http\Requests\LX;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePwdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'oldPwd' => 'required|string',
            'newPwd' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'oldPwd.required' => '原密码不能为空',
            'newPwd.required' => '新密码不能为空',
            'newPwd.min'      => '新密码不能少于6个字符',
        ];
    }
}
