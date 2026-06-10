<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Reject faker / disposable domains on production staff accounts.
 */
class ProductionStaffEmail implements ValidationRule
{
    /** @var list<string> */
    private const BLOCKED_DOMAINS = [
        'example.com',
        'example.net',
        'example.org',
        'test.com',
        'localhost',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $email = strtolower(trim((string) $value));
        $domain = substr(strrchr($email, '@') ?: '', 1);

        if ($domain === '' || in_array($domain, self::BLOCKED_DOMAINS, true)) {
            $fail(__('Use a real organizational email address for staff accounts.'));
        }
    }
}
