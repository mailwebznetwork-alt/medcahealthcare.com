<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function updating(User $user): void
    {
        if (! $user->isDirty('password')) {
            return;
        }

        $actorId = auth()->id();
        $source = $actorId !== null ? 'authenticated' : 'unauthenticated/system';
        $ip = request()->ip() ?? 'cli';

        $message = sprintf(
            'Password changed for user ID %d (%s). source=%s ip=%s actor=%s',
            $user->id,
            $user->email,
            $source,
            $ip,
            $actorId ?? 'none',
        );

        Log::warning($message);

        app(ActivityLogService::class)->log(
            'password_changed',
            'security',
            $message,
        );
    }
}
