<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\PhoneNumber;

class AdminSendCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', new PhoneNumber()],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => '手机号不能为空',
        ];
    }
}
