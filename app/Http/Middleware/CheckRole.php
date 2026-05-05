<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            $this->activityLogService->log(
                'unauthorized_access_attempt',
                'rbac',
                'Unauthenticated request blocked by role middleware.'
            );

            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }

        $allowedRoles = collect($roles)
            ->flatMap(static fn (string $set): array => explode(',', $set))
            ->map(static fn (string $role): string => trim($role))
            ->filter(static fn (string $role): bool => $role !== '')
            ->values()
            ->all();

        $authenticatedUser = auth()->user();
        $currentRole = trim((string) ($authenticatedUser->role ?? ''));

        if ($currentRole === '' && method_exists($authenticatedUser, 'isRootSuperAdmin') && $authenticatedUser->isRootSuperAdmin()) {
            $currentRole = 'super_admin';
        }

        if ($currentRole === '') {
            $roleLabel = trim((string) ($authenticatedUser->role_label ?? ''));
            $normalizedRole = mb_strtolower(str_replace(' ', '_', $roleLabel));
            $validRoles = ['super_admin', 'admin', 'manager', 'editor', 'viewer'];

            if (in_array($normalizedRole, $validRoles, true)) {
                $currentRole = $normalizedRole;
            }
        }

        if ($currentRole === '') {
            $currentRole = 'viewer';
        }

        if (! in_array($currentRole, $allowedRoles, true)) {
            $this->activityLogService->log(
                'role_violation',
                'rbac',
                sprintf(
                    'Role "%s" denied for route "%s". Allowed: %s',
                    $currentRole,
                    $request->route()?->getName() ?? 'unknown',
                    implode(',', $allowedRoles)
                )
            );

            return new JsonResponse(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
