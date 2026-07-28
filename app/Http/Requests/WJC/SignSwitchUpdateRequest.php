<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class SignSwitchUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => 'required|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => '开关值不能为空',
            'value.in'       => '开关值只能为0(关闭)或1(开启)',
        ];
    }
}
