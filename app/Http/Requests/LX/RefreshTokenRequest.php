<?php
// 刷新令牌请求
namespace App\Http\Requests\LX;

use Illuminate\Foundation\Http\FormRequest;

class RefreshTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refreshToken' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'refreshToken.required' => 'refreshToken不能为空',
        ];
    }
}
