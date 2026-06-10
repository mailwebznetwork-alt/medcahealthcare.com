<?php

namespace App\Rules;

use App\Support\FakerContentGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RejectFakerContent implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $guard = app(FakerContentGuard::class);

        if (! $guard->applies()) {
            return;
        }

        if ($guard->isFakerLike(is_string($value) ? $value : null)) {
            $fail($guard->validationMessage());
        }
    }
}
