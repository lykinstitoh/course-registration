<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class KenyanPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            $fail('Enter a valid Kenyan mobile number (07XX XXX XXX or 2547XXXXXXXX).');

            return;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        // 07XXXXXXXX / 01XXXXXXXX (10 digits) or 2547XXXXXXXX / 2541XXXXXXXX (12 digits)
        $valid = (bool) preg_match('/^(?:254|0)(?:1|7)\d{8}$/', $digits);

        if (! $valid) {
            $fail('Enter a valid Kenyan mobile number (07XX XXX XXX or 2547XXXXXXXX).');
        }
    }
}
