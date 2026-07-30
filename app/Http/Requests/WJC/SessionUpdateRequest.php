<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class SessionUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'       => 'nullable|string|max:100',
            'content'     => 'nullable|string',
            'sessionDate' => 'nullable|date',
            'endTime'     => 'nullable|date',
            'location'    => 'nullable|string|max:200',
            'instructor'  => 'nullable|string|max:50',
            'sortOrder'   => 'nullable|integer|min:0',
            'status'      => 'nullable|integer|in:0,1',
        ];
    }
}
