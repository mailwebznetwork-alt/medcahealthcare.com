<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Collection;

class AdminNotificationRecipientResolver
{
    /**
     * @return Collection<int, int>
     */
    public function resolve(?int $actorUserId = null): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereIn('role', ['admin', 'super_admin'])
            ->when($actorUserId !== null, fn ($query) => $query->where('id', '!=', $actorUserId))
            ->pluck('id');
    }
}
