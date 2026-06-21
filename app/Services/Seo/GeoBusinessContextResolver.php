<?php

namespace App\Services\Seo;

use App\Models\BusinessProfile;
use App\Models\GeoLocation;
use App\Models\PinCode;
use App\Models\Service;
use App\Services\Growth\SeoEntityResolver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Resolves organization / business / GEO context from live records — no hardcoded localities.
 */
class GeoBusinessContextResolver
{
    public function __construct(
        private readonly SeoEntityResolver $seoEntityResolver,
    ) {}

    /**
     * @return array{
     *   site_url: string,
     *   brand: string,
     *   telephone: string|null,
     *   description: string|null,
     *   business_profile: BusinessProfile|null,
     *   seo_entity: \App\Models\SeoEntity|null,
     *   geo_location: GeoLocation|null,
     *   address: array<string, mixed>|null,
     *   geo_coordinates: array<string, mixed>|null,
     *   same_as: list<string>,
     *   knows_about: list<string>,
     *   area_served: list<array<string, mixed>>,
     *   service_catalog: list<array<string, mixed>>
     * }
     */
    public function resolve(?Service $contextService = null, ?PinCode $contextPin = null): array
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $brand = config('medca.brand_name', 'MarkOnMinds');
        $seoEntity = $this->seoEntityResolver->forCurrentBusiness();
        $profile = $this->resolveBusinessProfile($seoEntity);

        $geoLocation = $this->resolveGeoLocation($contextPin, $profile);
        $telephone = $profile?->phone_e164 ?: $profile?->phone ?: config('medca.phone_tel');
        $description = $seoEntity?->meta_description
            ?: ($profile?->name ? $brand.' — '.$profile->name : null);

        $address = $this->buildAddress($profile, $contextPin);
        $geoCoordinates = $this->buildGeoCoordinates($geoLocation, $contextPin);
        $sameAs = $this->normalizeSameAs($seoEntity?->same_as ?? [], $seoEntity?->google_business_profile_url);
        $knowsAbout = $this->collectKnowsAbout($contextService);
        $areaServed = $this->collectAreaServed($contextService, $contextPin, $geoLocation);
        $serviceCatalog = $this->buildServiceCatalog($contextService);

        return [
            'site_url' => $siteUrl,
            'brand' => $seoEntity?->organization_name ?: $brand,
            'telephone' => is_string($telephone) && $telephone !== '' ? $telephone : null,
            'description' => is_string($description) && trim($description) !== '' ? trim($description) : null,
            'business_profile' => $profile,
            'seo_entity' => $seoEntity,
            'geo_location' => $geoLocation,
            'address' => $address,
            'geo_coordinates' => $geoCoordinates,
            'same_as' => $sameAs,
            'knows_about' => $knowsAbout,
            'area_served' => $areaServed,
            'service_catalog' => $serviceCatalog,
        ];
    }

    private function resolveBusinessProfile(?\App\Models\SeoEntity $seoEntity): ?BusinessProfile
    {
        if (! Schema::hasTable('business_profiles')) {
            return null;
        }

        if ($seoEntity?->business_profile_id) {
            $profile = BusinessProfile::query()->find($seoEntity->business_profile_id);
            if ($profile !== null) {
                return $profile;
            }
        }

        return BusinessProfile::query()
            ->where('website', config('app.url'))
            ->first()
            ?? BusinessProfile::query()->latest('id')->first();
    }

    private function resolveGeoLocation(?PinCode $pin, ?BusinessProfile $profile): ?GeoLocation
    {
        if (! Schema::hasTable('geo_locations')) {
            return null;
        }

        if ($pin?->geo_location_id) {
            $loc = GeoLocation::query()->where('is_active', true)->find($pin->geo_location_id);
            if ($loc !== null) {
                return $loc;
            }
        }

        if ($profile !== null) {
            return GeoLocation::query()
                ->where('business_profile_id', $profile->id)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
        }

        return GeoLocation::query()->where('is_active', true)->orderByDesc('id')->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildAddress(?BusinessProfile $profile, ?PinCode $pin): ?array
    {
        if ($pin !== null) {
            $locality = $pin->area_name ?: $pin->locality ?: $pin->city;
            if ($locality !== null && $locality !== '') {
                return array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $pin->locality ?: null,
                    'addressLocality' => $locality,
                    'addressRegion' => $pin->state,
                    'postalCode' => $pin->pincode,
                    'addressCountry' => $profile?->country_code ?: 'IN',
                ], fn ($v) => $v !== null && $v !== '');
            }
        }

        if ($profile === null) {
            return null;
        }

        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $profile->street_address ?: $profile->address,
            'addressLocality' => $profile->city,
            'addressRegion' => $profile->region,
            'postalCode' => $profile->postal_code,
            'addressCountry' => $profile->country_code ?: 'IN',
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildGeoCoordinates(?GeoLocation $geoLocation, ?PinCode $pin): ?array
    {
        if ($geoLocation !== null && $geoLocation->latitude !== null && $geoLocation->longitude !== null) {
            return [
                '@type' => 'GeoCoordinates',
                'latitude' => (string) $geoLocation->latitude,
                'longitude' => (string) $geoLocation->longitude,
            ];
        }

        $pin?->loadMissing('geoLocation');
        if ($pin?->geoLocation && $pin->geoLocation->latitude !== null) {
            return [
                '@type' => 'GeoCoordinates',
                'latitude' => (string) $pin->geoLocation->latitude,
                'longitude' => (string) $pin->geoLocation->longitude,
            ];
        }

        return null;
    }

    /**
     * @param  mixed  $sameAs
     * @return list<string>
     */
    private function normalizeSameAs(mixed $sameAs, ?string $gbpUrl): array
    {
        $urls = is_array($sameAs) ? $sameAs : [];
        if (filled($gbpUrl)) {
            $urls[] = $gbpUrl;
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($u) => is_string($u) && filter_var($u, FILTER_VALIDATE_URL) ? $u : null,
            $urls
        ))));
    }

    /**
     * @return list<string>
     */
    private function collectKnowsAbout(?Service $contextService): array
    {
        $topics = [];

        if ($contextService !== null) {
            $contextService->loadMissing('seo');
            if (is_array($contextService->ai_keywords)) {
                $topics = array_merge($topics, $contextService->ai_keywords);
            }
            if (is_array($contextService->seo?->entity_tags)) {
                $topics = array_merge($topics, $contextService->seo->entity_tags);
            }
            if (filled($contextService->title)) {
                $topics[] = $contextService->title;
            }
        }

        if (Schema::hasTable('services')) {
            Service::query()
                ->publicListing()
                ->limit(12)
                ->pluck('title')
                ->each(function (string $title) use (&$topics): void {
                    $topics[] = $title;
                });
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($t) => is_string($t) ? trim($t) : null,
            $topics
        ))));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectAreaServed(?Service $contextService, ?PinCode $contextPin, ?GeoLocation $geoLocation): array
    {
        $areas = [];

        if ($contextPin !== null) {
            $areas[] = $this->placeNode(
                $contextPin->area_name ?: $contextPin->locality ?: $contextPin->city ?: $contextPin->pincode,
                $contextPin->pincode,
                $contextPin->city,
                $contextPin->state
            );
            $contextPin->loadMissing('nearbyAreas');
            foreach ($contextPin->nearbyAreas as $nearby) {
                $areas[] = $this->placeNode($nearby->area_name);
            }
        }

        if ($contextService !== null) {
            $contextService->loadMissing('pincodes');
            foreach ($contextService->pincodes as $pin) {
                $areas[] = $this->placeNode(
                    $pin->area_name ?: $pin->locality ?: $pin->city ?: $pin->pincode,
                    $pin->pincode,
                    $pin->city,
                    $pin->state
                );
            }
        }

        if ($geoLocation !== null && filled($geoLocation->label)) {
            $geoArea = [
                '@type' => 'GeoCircle',
                'name' => $geoLocation->label,
            ];
            if ($geoLocation->latitude !== null && $geoLocation->longitude !== null) {
                $geoArea['geoMidpoint'] = [
                    '@type' => 'GeoCoordinates',
                    'latitude' => (string) $geoLocation->latitude,
                    'longitude' => (string) $geoLocation->longitude,
                ];
            }
            if ($geoLocation->radius_km) {
                $geoArea['geoRadius'] = $geoLocation->radius_km * 1000;
            }
            $areas[] = $geoArea;
        }

        return $this->dedupeAreas($areas);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildServiceCatalog(?Service $focus): array
    {
        if (! Schema::hasTable('services')) {
            return [];
        }

        $query = Service::query()->publicListing()->orderBy('sort_order');
        if ($focus !== null) {
            $query->whereKey($focus->id);
        } else {
            $query->limit(20);
        }

        return $query->get()->map(fn (Service $s): array => [
            '@type' => 'Offer',
            'itemOffered' => [
                '@type' => 'Service',
                'name' => $s->title,
                'url' => $s->publicUrl(),
            ],
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function placeNode(string $name, ?string $postalCode = null, ?string $city = null, ?string $region = null): array
    {
        return array_filter([
            '@type' => 'Place',
            'name' => $name,
            'postalCode' => $postalCode,
            'address' => ($city || $region) ? array_filter([
                '@type' => 'PostalAddress',
                'addressLocality' => $city,
                'addressRegion' => $region,
                'postalCode' => $postalCode,
                'addressCountry' => 'IN',
            ]) : null,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $areas
     * @return list<array<string, mixed>>
     */
    private function dedupeAreas(array $areas): array
    {
        $seen = [];
        $out = [];
        foreach ($areas as $area) {
            $key = ($area['name'] ?? '').'|'.($area['postalCode'] ?? '').'|'.($area['@type'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $area;
        }

        return $out;
    }
}
