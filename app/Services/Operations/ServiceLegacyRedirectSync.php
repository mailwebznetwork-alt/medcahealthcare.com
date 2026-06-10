<?php

namespace App\Services\Operations;

use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Models\SiteSlugRedirect;

/**
 * Registers 301 chains from legacy /p/{cms-slug} paths to public /services/... URLs.
 */
class ServiceLegacyRedirectSync
{
    public function __construct(
        private readonly ServicePublicUrlBuilder $urlBuilder,
    ) {}

    public function syncForService(Service $service): void
    {
        $service->loadMissing(['detailPage', 'locationPages']);

        $detail = $service->detailPage;
        if ($detail !== null && filled($detail->slug)) {
            $target = $this->urlBuilder->serviceUrl($service);
            $this->upsertRedirect($detail->slug, $target);
        }

        $patternSlug = app(ServiceDetailPageProvisioner::class)->suggestedSlug($service);
        if ($patternSlug !== $detail?->slug) {
            $this->upsertRedirect($patternSlug, $this->urlBuilder->serviceUrl($service));
        }

        foreach ($service->locationPages as $mapping) {
            if (! filled($mapping->slug)) {
                continue;
            }
            $mapping->loadMissing(['pincode', 'service']);
            if ($mapping->service === null || $mapping->pincode === null) {
                continue;
            }
            $this->upsertRedirect(
                $mapping->slug,
                $this->urlBuilder->locationUrlForPin($mapping->service, $mapping->pincode, $mapping)
            );
        }
    }

    public function removeForService(Service $service): void
    {
        $this->bulkRemoveForServiceIds([$service->id]);
    }

    /**
     * @param  list<int>  $serviceIds
     */
    public function bulkRemoveForServiceIds(array $serviceIds): void
    {
        if ($serviceIds === []) {
            return;
        }

        $detailProvisioner = app(ServiceDetailPageProvisioner::class);
        $slugs = [];

        Service::query()
            ->whereIn('id', $serviceIds)
            ->with('detailPage:id,slug')
            ->get(['id', 'service_code', 'detail_page_id'])
            ->each(function (Service $service) use ($detailProvisioner, &$slugs): void {
                if ($service->detailPage !== null && filled($service->detailPage->slug)) {
                    $slugs[] = $service->detailPage->slug;
                }

                $slugs[] = $detailProvisioner->suggestedSlug($service);
            });

        $locationSlugs = ServiceLocationPage::query()
            ->whereIn('service_id', $serviceIds)
            ->pluck('slug')
            ->all();

        $all = array_values(array_unique(array_filter(array_merge($slugs, $locationSlugs))));

        if ($all === []) {
            return;
        }

        SiteSlugRedirect::query()->whereIn('from_slug', $all)->delete();
    }

    private function upsertRedirect(string $fromSlug, string $absoluteTarget): void
    {
        $path = parse_url($absoluteTarget, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return;
        }

        SiteSlugRedirect::query()->updateOrCreate(
            ['from_slug' => $fromSlug],
            ['to_slug' => ltrim($path, '/')]
        );
    }
}
