<?php

namespace App\Services\Marketing\Attribution;

use App\Services\Public\PublicRouteAttributionResolver;
use App\Services\UserLocationService;
use Illuminate\Http\Request;

class LandingPageContextResolver
{
    public function __construct(
        private readonly PublicRouteAttributionResolver $routeResolver,
        private readonly UserLocationService $location,
    ) {}

    public function resolve(Request $request, ?string $landingPageOverride = null): LandingContext
    {
        $visitorPinCodeId = $this->location->currentPinCodeRecord()?->id;

        $pathSource = $landingPageOverride;
        if (! is_string($pathSource) || trim($pathSource) === '') {
            $pathSource = '/'.ltrim($request->path(), '/');
            if ($pathSource === '//') {
                $pathSource = '/';
            }
        }

        return $this->routeResolver->resolveFromPath($pathSource, $visitorPinCodeId);
    }
}
