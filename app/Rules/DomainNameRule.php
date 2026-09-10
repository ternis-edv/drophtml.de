<?php

namespace App\Rules;

use App\Services\DomainService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class DomainNameRule implements ValidationRule
{
    public function __construct(
        protected ?DomainService $domainService = null
    ) {
        $this->domainService ??= app(DomainService::class);
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid domain string.');

            return;
        }

        $normalized = $this->domainService->normalizeDomain($value);

        if (! preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i', $normalized)) {
            $fail('The :attribute must be a valid domain name (e.g. example.com or sub.example.com).');

            return;
        }

        if ($this->domainService->isReserved($normalized)) {
            $fail("The domain '{$normalized}' is reserved by the platform and cannot be registered.");
        }
    }
}
