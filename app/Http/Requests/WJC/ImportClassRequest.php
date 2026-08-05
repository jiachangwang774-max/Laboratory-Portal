<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class ImportClassRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file' => 'required|file|max:10240',
        ];
    }

    public function messages(): array
    {
        return ['file.required' => '请选择要导入的Excel文件'];
    }
}
