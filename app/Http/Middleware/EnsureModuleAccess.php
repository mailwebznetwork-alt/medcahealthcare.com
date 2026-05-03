<?php

namespace App\Http\Middleware;

use App\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if (! ModuleAccess::isValidKey($module)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if (! $user->hasModuleAccess($module)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
