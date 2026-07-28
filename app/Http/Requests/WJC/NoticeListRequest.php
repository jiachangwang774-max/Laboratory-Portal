<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class NoticeListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'  => 'nullable|integer|min:1',
            'size'  => 'nullable|integer|min:1|max:100',
            'title' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => '页码必须为整数',
            'page.min'     => '页码不能小于1',
            'size.integer' => '每页条数必须为整数',
            'size.max'     => '每页条数不能超过100',
        ];
    }
}
