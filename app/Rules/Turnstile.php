<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('services.turnstile.enabled')) {
            return;
        }

        if (! is_string($value) || trim($value) === '') {
            $fail('Security verification is required.');

            return;
        }

        $response = Http::asForm()
            ->timeout(5)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

        if (! $response->ok() || $response->json('success') !== true) {
            $fail('Security verification failed. Please try again.');
        }
    }
}
