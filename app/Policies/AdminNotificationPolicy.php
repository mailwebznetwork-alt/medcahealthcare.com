<?php

namespace App\Policies;

use App\Models\AdminNotification;
use App\Models\User;

class AdminNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdminRecipient($user);
    }

    public function view(User $user, AdminNotification $notification): bool
    {
        return $this->isAdminRecipient($user)
            && (int) $notification->recipient_user_id === (int) $user->id;
    }

    public function update(User $user, AdminNotification $notification): bool
    {
        return $this->view($user, $notification);
    }

    private function isAdminRecipient(User $user): bool
    {
        return in_array(strtolower((string) $user->role), ['admin', 'super_admin'], true);
    }
}
