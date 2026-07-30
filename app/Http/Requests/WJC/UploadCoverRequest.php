<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class UploadCoverRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cover' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'cover.required' => '请选择要上传的封面图片',
            'cover.image'    => '文件必须是图片',
            'cover.mimes'    => '封面仅支持 jpeg、png、jpg、gif、webp 格式',
            'cover.max'      => '封面大小不能超过 5MB',
        ];
    }
}
