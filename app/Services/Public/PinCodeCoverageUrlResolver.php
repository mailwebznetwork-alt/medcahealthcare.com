<?php

namespace App\Services\Public;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Support\CoverageLinkContext;
use Illuminate\Support\Collection;

class PinCodeCoverageUrlResolver
{
    public function __construct(
        private readonly PinCodeAreaResolver $areaResolver,
    ) {}

    /**
     * @param  Collection<int, PinCode>  $pins
     * @return array<int, string>
     */
    public function urlsFor(Collection $pins, ?Service $service = null, ?ServiceCategory $category = null): array
    {
        if ($pins->isEmpty()) {
            return [];
        }

        $urls = [];
        $needsMapping = collect();

        foreach ($pins as $pin) {
            $quick = $this->quickUrl($pin, $service, $category);
            if ($quick !== null) {
                $urls[$pin->id] = $quick;
            } else {
                $needsMapping->push($pin);
            }
        }

        if ($needsMapping->isEmpty()) {
            return $urls;
        }

        $mappingsByPin = $this->indexableMappingsFor($needsMapping, $service);

        foreach ($needsMapping as $pin) {
            $urls[$pin->id] = $this->urlFor($pin, $mappingsByPin->get($pin->id), $service, $category);
        }

        return $urls;
    }

    /**
     * @param  Collection<int, ServiceLocationPage>|null  $mappings
     */
    public function urlFor(PinCode $pin, ?Collection $mappings = null, ?Service $service = null, ?ServiceCategory $category = null): string
    {
        $quick = $this->quickUrl($pin, $service, $category);
        if ($quick !== null) {
            return $quick;
        }

        $mapping = $this->firstMapping($mappings);
        if ($mapping === null) {
            $mapping = $this->firstMapping(
                $this->indexableMappingsFor(collect([$pin]), $service)->get($pin->id)
            );
        }

        if ($mapping instanceof ServiceLocationPage) {
            return CoverageLinkContext::append($mapping->publicUrl(), $category);
        }

        if ($pin->is_active && $pin->geo_page_ready) {
            return CoverageLinkContext::append(
                $this->areaResolver->publicUrlFor($pin),
                $category,
                $service instanceof Service ? $service : null,
            );
        }

        return url('/locations');
    }

    private function quickUrl(PinCode $pin, ?Service $service = null, ?ServiceCategory $category = null): ?string
    {
        if (filled($pin->landing_page)) {
            $landing = (string) $pin->landing_page;

            return str_starts_with($landing, ['http://', 'https://'])
                ? $landing
                : url('/'.ltrim($landing, '/'));
        }

        if ($service instanceof Service) {
            return null;
        }

        if ($pin->is_active && $pin->geo_page_ready) {
            return CoverageLinkContext::append(
                $this->areaResolver->publicUrlFor($pin),
                $category,
            );
        }

        return null;
    }

    /**
     * @param  Collection<int, PinCode>  $pins
     * @return Collection<int, Collection<int, ServiceLocationPage>>
     */
    private function indexableMappingsFor(Collection $pins, ?Service $service = null): Collection
    {
        $query = ServiceLocationPage::query()
            ->whereIn('pincode_id', $pins->pluck('id'))
            ->where('is_indexable', true)
            ->with([
                'service:id,service_code,is_active,publish_status,visibility,title',
                'page:id,is_active,robots_meta',
                'pincode:id,pincode,city_slug,location_slug',
            ]);

        if ($service instanceof Service) {
            $query->where('service_id', $service->id);
        }

        return $query
            ->get()
            ->filter(fn (ServiceLocationPage $mapping): bool => $mapping->isPubliclyIndexable())
            ->groupBy('pincode_id')
            ->map(fn (Collection $group): Collection => $group->values());
    }

    private function firstMapping(mixed $mappings): ?ServiceLocationPage
    {
        if ($mappings instanceof ServiceLocationPage) {
            return $mappings;
        }

        if ($mappings instanceof Collection) {
            $first = $mappings->first();

            return $first instanceof ServiceLocationPage ? $first : null;
        }

        return null;
    }
}
