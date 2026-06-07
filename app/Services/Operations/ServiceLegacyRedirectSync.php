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
        $slugs = [];
        if ($service->detail_page_id !== null) {
            $page = $service->detailPage;
            if ($page !== null) {
                $slugs[] = $page->slug;
            }
        }
        $slugs[] = app(ServiceDetailPageProvisioner::class)->suggestedSlug($service);

        ServiceLocationPage::query()
            ->where('service_id', $service->id)
            ->pluck('slug')
            ->each(fn (string $s) => $slugs[] = $s);

        SiteSlugRedirect::query()
            ->whereIn('from_slug', array_filter($slugs))
            ->delete();
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
