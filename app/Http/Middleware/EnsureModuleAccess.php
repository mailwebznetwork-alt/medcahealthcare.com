<?php

namespace App\Http\Middleware;

use App\ModuleAccess;
use App\Support\UserLandingRoute;
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
            $landing = UserLandingRoute::pathFor($user);
            $loginPath = route('login', absolute: false);

            if ($landing !== $loginPath && $landing !== '/'.$request->path()) {
                return redirect()->to($landing);
            }

            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
