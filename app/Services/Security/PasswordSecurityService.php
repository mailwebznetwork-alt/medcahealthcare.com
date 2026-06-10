<?php

namespace App\Services\Security;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PasswordSecurityService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    /**
     * Apply a password change for the given user, invalidate other sessions, and audit.
     */
    public function changePassword(User $user, string $plainPassword, string $context, ?int $actorId = null): void
    {
        $user->password = $plainPassword;
        $user->save();

        $this->finalizePasswordChange($user, $plainPassword, $context, $actorId);
    }

    /**
     * Session invalidation + success audit after the password hash is already persisted.
     */
    public function finalizePasswordChange(User $user, string $plainPassword, string $context, ?int $actorId = null): void
    {
        $this->invalidateOtherSessions($user, $plainPassword);

        $this->logSuccess($user, $context, $actorId);
    }

    /**
     * Invalidate sessions for a user (e.g. admin reset or email token reset).
     */
    public function invalidateAllSessionsFor(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Log a failed password change attempt (validation or wrong current password).
     */
    public function logFailure(
        Request $request,
        string $context,
        string $reason,
        ?int $targetUserId = null,
    ): void {
        $actorId = $request->user()?->id;
        $ip = (string) ($request->ip() ?? 'unknown');

        $message = sprintf(
            'Password change FAILED context=%s reason=%s actor=%s target=%s ip=%s',
            $context,
            $reason,
            $actorId ?? 'guest',
            $targetUserId ?? ($actorId ?? 'unknown'),
            $ip,
        );

        Log::warning($message);

        $this->activityLogService->log('password_change_failed', 'security', $message);
    }

    /**
     * Require the authenticated actor to confirm their own password before a privileged action.
     *
     * @throws ValidationException
     */
    public function assertActorPassword(Request $request, string $field = 'admin_password'): void
    {
        $password = (string) $request->input($field, '');

        if ($password === '' || ! Auth::guard('web')->validate([
            'email' => $request->user()?->email,
            'password' => $password,
        ])) {
            $this->logFailure($request, 'user_management', 'invalid_actor_password');

            throw ValidationException::withMessages([
                $field => [__('Your password confirmation was incorrect.')],
            ]);
        }
    }

    private function invalidateOtherSessions(User $user, string $plainPassword): void
    {
        if (Auth::id() === $user->id) {
            Auth::logoutOtherDevices($plainPassword);

            return;
        }

        $this->invalidateAllSessionsFor($user);
    }

    private function logSuccess(User $user, string $context, ?int $actorId): void
    {
        $ip = (string) (request()->ip() ?? 'cli');

        $message = sprintf(
            'Password change SUCCESS context=%s user_id=%d email=%s actor=%s ip=%s',
            $context,
            $user->id,
            $user->email,
            $actorId ?? (auth()->id() ?? 'system'),
            $ip,
        );

        Log::warning($message);
    }
}
