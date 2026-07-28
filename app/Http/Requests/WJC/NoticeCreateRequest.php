<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class NoticeCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'   => 'required|string|max:100',
            'content' => 'required|string',
            'cover'   => 'nullable|string|max:255',
            'isTop'   => 'nullable|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => '公告标题不能为空',
            'title.max'        => '公告标题不能超过100个字符',
            'content.required' => '公告内容不能为空',
            'cover.max'        => '封面图片地址不能超过255个字符',
            'isTop.in'         => '置顶参数只能为0或1',
        ];
    }
}
