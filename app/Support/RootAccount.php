<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

final class RootAccount
{
    public static function email(): string
    {
        return (string) config('root_account.email', '');
    }

    public static function isRootUser(?Authenticatable $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $configured = self::email();

        if ($configured === '') {
            return false;
        }

        return strtolower($user->email) === strtolower($configured);
    }
}
