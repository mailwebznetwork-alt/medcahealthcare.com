<?php

namespace App\Services\Discovery;

use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Operations\ServicePublicUrlBuilder;
use Illuminate\Http\Request;

/**
 * Resolves where a visitor should land after changing their session pincode.
 */
class PincodeRedirectResolver
{
    public function __construct(
        private readonly ServicePublicUrlBuilder $urls,
    ) {}

    public function resolveAfterSwitch(Request $request, string $pincode): string
    {
        $path = trim($request->path(), '/');
        $segments = $path !== '' ? explode('/', $path) : [];

        if (($segments[0] ?? null) === 'services') {
            return $this->resolveServicesPath($segments, $pincode);
        }

        if ($path === 'services-catalog') {
            return url('/services-catalog');
        }

        if ($path === 'locations' || str_starts_with($path, 'locations/')) {
            return url('/locations').'#near-you';
        }

        if ($path === '' || $path === 'home') {
            return url('/').'#near-you';
        }

        if (($segments[0] ?? null) === 'service-categories') {
            return $request->fullUrl();
        }

        return url('/locations').'#near-you';
    }

    /**
     * @param  list<string>  $segments
     */
    private function resolveServicesPath(array $segments, string $pincode): string
    {
        $serviceCode = $segments[1] ?? null;

        if (! is_string($serviceCode) || $serviceCode === '' || $serviceCode === 'sub') {
            return url('/locations').'#near-you';
        }

        return $this->resolveForServiceCode($serviceCode, $pincode);
    }

    public function resolveForServiceCode(string $serviceCode, string $pincode): string
    {
        $service = Service::findPubliclyViewableByCode($serviceCode);

        if ($service === null) {
            return url('/locations').'#near-you';
        }

        if (! $service->isAvailableInPincode($pincode)) {
            return url('/locations').'#near-you';
        }

        $mapping = $this->findPublicLocationMapping($service->id, $pincode);

        if ($mapping !== null) {
            return $mapping->publicUrl();
        }

        return $this->urls->serviceUrl($service);
    }

    private function findPublicLocationMapping(int $serviceId, string $pincode): ?ServiceLocationPage
    {
        return ServiceLocationPage::query()
            ->where('service_id', $serviceId)
            ->whereHas('pincode', fn ($query) => $query->where('pincode', $pincode))
            ->with(['service', 'pincode', 'page'])
            ->get()
            ->first(fn (ServiceLocationPage $mapping): bool => $mapping->isPubliclyIndexable());
    }
}
