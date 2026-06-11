<?php

namespace App\Http\Middleware;

use App\Services\Marketing\Attribution\AttributionSessionPersister;
use App\Services\Marketing\Attribution\LandingPageContextResolver;
use App\Services\Marketing\Attribution\UtmCaptureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureMarketingAttributionMiddleware
{
    public function __construct(
        private readonly UtmCaptureService $utmCapture,
        private readonly LandingPageContextResolver $landingContextResolver,
        private readonly AttributionSessionPersister $sessionPersister,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (config('marketing_automation.enabled', true) && config('marketing_automation.attribution.enabled', true)) {
            $this->utmCapture->captureFromRequest($request);

            if (config('marketing_attribution.enabled', true)
                && $this->sessionPersister->shouldPersistForRequest($request)) {
                $context = $this->landingContextResolver->resolve($request);
                $this->sessionPersister->persist($request, $context);
            }
        }

        return $next($request);
    }
}
