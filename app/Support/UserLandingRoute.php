<?php

namespace App\Support;

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Support\Facades\Route;

/**
 * First route for a module the super administrator has granted on the account.
 */
final class UserLandingRoute
{
    public static function pathFor(?User $user): string
    {
        if ($user === null) {
            return route('login', absolute: false);
        }

        foreach (ModuleAccess::keys() as $key) {
            if (! $user->hasModuleAccess($key)) {
                continue;
            }

            $routeName = ModuleAccess::navigation()[$key]['route'] ?? null;
            if (! is_string($routeName) || $routeName === '' || ! Route::has($routeName)) {
                continue;
            }

            return route($routeName, absolute: false);
        }

        return route('login', absolute: false);
    }
}
