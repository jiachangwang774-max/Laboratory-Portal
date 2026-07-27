<?php

namespace App\Http\Requests\LX;

use Illuminate\Foundation\Http\FormRequest;

class ResetPwdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'  => 'required|string|size:11',
            'code'   => 'required|string|size:6',
            'newPwd' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'  => '手机号不能为空',
            'phone.size'      => '手机号必须为11位',
            'code.required'   => '验证码不能为空',
            'code.size'       => '验证码必须为6位',
            'newPwd.required' => '新密码不能为空',
            'newPwd.min'      => '新密码不能少于6个字符',
        ];
    }
}
