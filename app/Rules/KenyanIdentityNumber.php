<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class KenyanIdentityNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Enter a National ID, Maisha UPI, passport, or birth-certificate number.');

            return;
        }

        $value = trim($value);

        $patterns = [
            // Classic National ID (7–9 digits; most are 8)
            '/^\d{7,9}$/',
            // Maisha Unique Personal Identifier (14 digits)
            '/^\d{14}$/',
            // Kenyan / ordinary passport styles
            '/^[A-Za-z]{1,2}\d{6,9}$/',
            // Birth certificate / entry serials (letters, digits, / or -)
            '/^[A-Za-z0-9]+(?:[\/\-][A-Za-z0-9]+)+$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return;
            }
        }

        $fail('Use a valid National ID (7–9 digits), Maisha number (14 digits), passport, or birth-certificate number.');
    }
}
