<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            $fail('The :attribute contains an invalid URL.');
            return;
        }

        if ((str_starts_with($value, '/') && ! str_starts_with($value, '//'))
            || preg_match('/^(?:https?:\/\/|mailto:|tel:|#|\?)/i', $value) === 1) {
            return;
        }

        $fail('The :attribute must be an internal path or an HTTP(S), email, or telephone URL.');
    }
}
