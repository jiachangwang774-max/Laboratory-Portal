<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\PasswordStrength;

class AdminUpdatePwdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'oldPwd' => 'required|string',
            'newPwd' => ['required', 'string', new PasswordStrength()],
        ];
    }

    public function messages(): array
    {
        return [
            'oldPwd.required' => '原密码不能为空',
            'newPwd.required' => '新密码不能为空',
        ];
    }
}
