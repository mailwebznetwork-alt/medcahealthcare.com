<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AutoLogout
{
    private const TIMEOUT_SECONDS = 1800;

    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $next($request);
        }

        if (! Auth::guard('web')->check()) {
            return $next($request);
        }

        if (! $request->hasSession()) {
            return $next($request);
        }

        $session = $request->session();
        $lastActivity = (int) $session->get('last_activity', 0);
        $now = time();

        if ($lastActivity > 0 && ($now - $lastActivity) > self::TIMEOUT_SECONDS) {
            $this->activityLogService->log(
                'session_timeout',
                'auth',
                sprintf('Session auto-logout after %d seconds.', self::TIMEOUT_SECONDS)
            );

            Auth::guard('web')->logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()->route('login');
        }

        $session->put('last_activity', $now);

        return $next($request);
    }
}
