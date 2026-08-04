<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class HomeworkUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'homeworkTitle'   => 'nullable|string|max:100',
            'homeworkContent' => 'nullable|string',
            'deadline'        => 'nullable|date',
            'groupName'       => 'nullable|string|in:一班,二班,三班',
        ];
    }
}
