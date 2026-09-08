<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class SignListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'        => 'nullable|integer|min:1',
            'size'        => 'nullable|integer|min:1|max:100',
            'auditStatus' => 'nullable|integer|in:0,1,2',
            'college'     => 'nullable|string|max:100',
            'major'       => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer'        => '页码必须为整数',
            'size.max'            => '每页条数不能超过100',
            'auditStatus.integer' => '审核状态必须为整数',
            'auditStatus.in'      => '审核状态只能为 0待审核 / 1通过 / 2驳回',
        ];
    }
}
