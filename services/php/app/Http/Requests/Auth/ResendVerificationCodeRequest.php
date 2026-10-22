<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResendVerificationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>|ValidationRule|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
