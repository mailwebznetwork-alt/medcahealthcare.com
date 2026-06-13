<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Query\Builder;

/**
 * Root-only visibility for detailed activity / security audit rows.
 */
final class ActivityLogVisibility
{
    public static function canViewDetailedAudit(?Authenticatable $user): bool
    {
        return $user instanceof User && $user->isRootSuperAdmin();
    }

    public static function applyViewerScope(Builder $query, ?Authenticatable $user): Builder
    {
        if (static::canViewDetailedAudit($user)) {
            return $query;
        }

        if ($user instanceof User) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('0 = 1');
    }
}
