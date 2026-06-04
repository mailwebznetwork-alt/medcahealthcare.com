<?php

namespace App\Http\Middleware;

use App\Services\UserLocationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsurePincodeDetected
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $location = app(UserLocationService::class);

        if (! $location->hasPincode()) {
            $location->detectFromIp($request);
        }

        View::share('locationRequired', ! $location->hasPincode());
        View::share('currentPincode', $location->currentPincode());
        View::share('currentPinCodeRecord', $location->currentPinCodeRecord());

        if ($this->requiresPincodeForServices($request) && ! $location->hasPincode()) {
            $request->attributes->set('services_blocked_until_pincode', true);
        }

        return $next($request);
    }

    private function shouldSkip(Request $request): bool
    {
        if ($request->is(
            'location/*',
            'api/*',
            'up',
            'login',
            'logout',
            'register',
            'password/*',
            'email/*',
            'verify-email',
            't/mail/*',
            'robots.txt',
            'sitemap*',
            'llm.txt',
            'ai-discovery',
            'livewire/*',
            'dashboard*',
            'operations/*',
            'site-architect/*',
            'growth-center/*',
            'user-management/*',
            'settings/*',
            'profile*',
            'marketing/*',
            'modules/*',
        )) {
            return true;
        }

        return false;
    }

    private function requiresPincodeForServices(Request $request): bool
    {
        return $request->routeIs(
            'public.home',
            'public.page.services',
            'public.services.index',
            'public.services.show',
            'public.service-categories.index',
            'public.service-categories.show',
        );
    }
}
