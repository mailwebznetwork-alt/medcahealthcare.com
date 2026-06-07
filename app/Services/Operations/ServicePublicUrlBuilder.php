<?php

namespace App\Services\Operations;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use Illuminate\Support\Str;

class ServicePublicUrlBuilder
{
    public function serviceUrl(Service $service): string
    {
        return url('/services/'.$service->service_code);
    }

    public function locationSlugForPin(PinCode $pin): string
    {
        $base = $pin->area_name ?: $pin->locality ?: $pin->city ?: $pin->pincode;

        return $this->uniqueLocationSlugForPin($this->slugify((string) $base), $pin);
    }

    public function citySlugForPin(PinCode $pin): string
    {
        $city = $pin->city ?: 'bangalore';

        return $this->slugify($city) ?: 'bangalore';
    }

    public function locationUrl(Service $service, string $locationSlug): string
    {
        return url('/services/'.$service->service_code.'/'.$locationSlug);
    }

    public function locationUrlForPin(Service $service, PinCode $pin, ?ServiceLocationPage $mapping = null): string
    {
        $locationSlug = $mapping?->location_slug
            ?: $this->locationSlugForPin($pin);

        if (config('services_master.public_url_include_pincode', false)) {
            $city = $mapping?->city_slug ?: $this->citySlugForPin($pin);

            return url('/services/'.$service->service_code.'/'.$city.'/'.$pin->pincode);
        }

        return $this->locationUrl($service, $locationSlug);
    }

    /**
     * Legacy CMS paths that should 301 to public service URLs.
     */
    public function legacyRedirectForPageSlug(string $pageSlug): ?string
    {
        $code = ServiceDetailPageProvisioner::serviceCodeFromPageSlug($pageSlug);
        if ($code !== null) {
            $service = Service::query()->where('service_code', $code)->first();
            if ($service !== null) {
                return $this->serviceUrl($service);
            }
        }

        $mapping = ServiceLocationPage::query()
            ->where('slug', $pageSlug)
            ->with(['service', 'pincode'])
            ->first();

        if ($mapping !== null && $mapping->service !== null && $mapping->pincode !== null) {
            return $this->locationUrlForPin($mapping->service, $mapping->pincode, $mapping);
        }

        return null;
    }

    public function slugify(string $value): string
    {
        $slug = Str::slug($value);

        return $slug !== '' ? $slug : 'location';
    }

    private function uniqueLocationSlugForPin(string $base, PinCode $pin): string
    {
        $slug = $base;
        $suffix = 0;

        while (
            ServiceLocationPage::query()
                ->where('location_slug', $slug)
                ->where('pincode_id', '!=', $pin->id)
                ->exists()
        ) {
            $suffix++;
            $slug = $base.'-'.$pin->pincode;
            if ($suffix > 1) {
                $slug = $base.'-'.$suffix;
            }
        }

        return $slug;
    }
}
