<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            $this->activityLogService->log(
                'unauthorized_access_attempt',
                'integrations',
                'Unauthenticated request blocked by admin middleware.'
            );

            if ($this->wantsJsonResponse($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'data' => [],
                ], 401);
            }

            return redirect()->guest(route('login'));
        }

        if (! $user->canAccessIntegrationsAdmin()) {
            $role = trim((string) ($user->role ?? ''));
            $this->activityLogService->log(
                'role_violation',
                'integrations',
                sprintf('User %d (role=%s) blocked by admin middleware.', (int) $user->id, $role)
            );

            if ($this->wantsJsonResponse($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden.',
                    'data' => [],
                ], 403);
            }

            abort(403);
        }

        return $next($request);
    }

    private function wantsJsonResponse(Request $request): bool
    {
        return $request->expectsJson() || $request->wantsJson() || $request->is('api/*');
    }
}
