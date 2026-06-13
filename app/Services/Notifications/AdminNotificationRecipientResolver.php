<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Support\RootAccount;
use Illuminate\Support\Collection;

class AdminNotificationRecipientResolver
{
    /**
     * @return Collection<int, int>
     */
    public function resolve(?int $actorUserId = null, ?string $action = null, ?string $module = null): Collection
    {
        if ($module === 'auth' && in_array($action, ['login_success', 'logout'], true)) {
            return $this->rootAdministratorIds();
        }

        $query = User::query()
            ->where('is_active', true)
            ->whereIn('role', ['admin', 'super_admin']);

        $notifyActor = $this->shouldNotifyActor($action, $module);

        if ($actorUserId !== null && ! $notifyActor) {
            $query->where('id', '!=', $actorUserId);
        }

        $recipientIds = $query->pluck('id');

        if ($notifyActor && $actorUserId !== null && ! $recipientIds->contains($actorUserId)) {
            return $recipientIds->push($actorUserId)->unique()->values();
        }

        return $recipientIds;
    }

    /**
     * @return Collection<int, int>
     */
    private function rootAdministratorIds(): Collection
    {
        $email = RootAccount::email();
        if ($email === '') {
            return collect();
        }

        $id = User::query()
            ->whereRaw('lower(email) = ?', [strtolower($email)])
            ->value('id');

        return $id !== null ? collect([(int) $id]) : collect();
    }

    private function shouldNotifyActor(?string $action, ?string $module): bool
    {
        if ($action !== null && in_array($action, config('notifications.actor_notify_actions', []), true)) {
            return true;
        }

        $normalizedModule = (string) config("notifications.module_map.{$module}", $module);

        if (in_array($normalizedModule, ['security', 'auth'], true) || in_array($module, ['security', 'auth'], true)) {
            return true;
        }

        if ($action === null) {
            return false;
        }

        $lower = strtolower($action);

        if (str_contains($lower, 'bulk_') || str_contains($lower, '_failure') || str_contains($lower, '_blocked')) {
            return true;
        }

        return preg_match('/(?:^|_)(delete|deleted|removed|destroy)(?:_|$)/', $lower) === 1;
    }
}
