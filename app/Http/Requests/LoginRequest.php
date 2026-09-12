<?php

namespace App\Http\Requests;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
            'cf-turnstile-response' => [new Turnstile()],
        ];
    }
}
