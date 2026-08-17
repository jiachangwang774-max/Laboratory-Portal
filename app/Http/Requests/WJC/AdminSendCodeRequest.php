<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class AdminSendCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adminName' => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'adminName.required' => '管理员账号不能为空',
            'adminName.max'      => '管理员账号不能超过50个字符',
        ];
    }
}
