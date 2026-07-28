<?php
// 上传头像请求
namespace App\Http\Requests\LX;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => '请选择要上传的头像',
            'avatar.image'    => '文件必须是图片',
            'avatar.mimes'    => '头像仅支持 jpeg、png、jpg、gif、webp 格式',
            'avatar.max'      => '头像大小不能超过 2MB',
        ];
    }
}
