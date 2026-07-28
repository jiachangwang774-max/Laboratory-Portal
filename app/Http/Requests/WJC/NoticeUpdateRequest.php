<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class NoticeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'   => 'nullable|string|max:100',
            'content' => 'nullable|string',
            'cover'   => 'nullable|string|max:255',
            'isTop'   => 'nullable|integer|in:0,1',
            'status'  => 'nullable|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'title.max'  => '公告标题不能超过100个字符',
            'cover.max'  => '封面图片地址不能超过255个字符',
            'isTop.in'   => '置顶参数只能为0或1',
            'status.in'  => '状态参数只能为0或1',
        ];
    }
}
