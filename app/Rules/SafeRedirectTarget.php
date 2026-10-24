<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects redirect targets whose protocol allows script or file execution
 * (javascript:, data:, vbscript:, file:). Shared by the API redirect
 * controller, the dashboard redirect component, and the _redirects importer.
 */
class SafeRedirectTarget implements ValidationRule
{
    /** @var list<string> */
    public const UNSAFE_PROTOCOLS = ['javascript:', 'data:', 'vbscript:', 'file:'];

    public static function isSafe(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $lower = strtolower(trim($value));

        foreach (self::UNSAFE_PROTOCOLS as $protocol) {
            if (str_starts_with($lower, $protocol)) {
                return false;
            }
        }

        return true;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isSafe($value)) {
            $fail('The destination target URL contains an unsafe protocol.');
        }
    }
}
