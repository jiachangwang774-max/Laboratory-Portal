<?php
// 交作业请求
namespace App\Http\Requests\LX;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HomeworkSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'homeworkId' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('train_homework', 'homework_id'),
            ],
            'content' => 'nullable|string',
            'file'    => 'nullable|file|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'homeworkId.required' => '作业ID不能为空',
            'homeworkId.integer'  => '作业ID必须为整数',
            'homeworkId.exists'   => '作业不存在',
            'file.file'           => '上传文件无效',
            'file.max'            => '文件大小不能超过10MB',
        ];
    }
}
