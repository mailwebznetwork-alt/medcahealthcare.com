<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Operations\ServiceLocationPageProvisioner;
use App\Support\DisplayLabelSanitizer;
use App\Support\ServicePageOverrides;

/**
 * Resolves public-facing labels from live Service / ServiceCategory records.
 * CMS Page snapshots are fallback only when admin authority locks a field.
 */
class PublicDisplayNameResolver
{
    public function __construct(
        private readonly ServiceLocationPageProvisioner $locationProvisioner,
    ) {}

    public function serviceHeadline(Service $service): string
    {
        $service->loadMissing('seo');

        return DisplayLabelSanitizer::clean(trim((string) ($service->seo?->h1 ?: $service->title)));
    }

    public function serviceMetaTitle(Service $service): string
    {
        $service->loadMissing('seo');

        return trim((string) ($service->seo?->meta_title ?: $service->title));
    }

    public function serviceMetaDescription(Service $service): ?string
    {
        $service->loadMissing('seo');
        $description = $service->seo?->meta_description ?: $service->short_summary;

        return filled($description) ? trim((string) $description) : null;
    }

    public function categoryHeadline(ServiceCategory $category): string
    {
        $category->loadMissing('seo');

        return DisplayLabelSanitizer::clean(trim((string) ($category->seo?->h1 ?: $category->name)));
    }

    public function categoryMetaTitle(ServiceCategory $category): string
    {
        $category->loadMissing('seo');

        return trim((string) ($category->seo?->meta_title ?: $category->name));
    }

    public function categoryMetaDescription(ServiceCategory $category): ?string
    {
        $category->loadMissing('seo');
        $description = $category->seo?->meta_description ?: $category->description;

        return filled($description) ? trim((string) $description) : null;
    }

    public function locationHeadline(Service $service, PinCode $pin): string
    {
        return $this->locationProvisioner->locationTitle($service, $pin);
    }

    public function locationMetaTitle(Service $service, PinCode $pin): string
    {
        return $this->locationProvisioner->locationMetaTitle($service, $pin);
    }

    public function locationMetaDescription(Service $service, PinCode $pin): ?string
    {
        return $this->locationProvisioner->localMetaDescription($service, $pin);
    }

    public function subServiceHeadline(SubService $sub): string
    {
        $sub->loadMissing('seo');

        return DisplayLabelSanitizer::clean(trim((string) ($sub->seo?->h1 ?: $sub->title)));
    }

    public function subServiceMetaTitle(SubService $sub): string
    {
        $sub->loadMissing('seo');

        return trim((string) ($sub->seo?->meta_title ?: $sub->title));
    }

    /**
     * @return array{title: string, meta_title: string, meta_description: string|null, prefer_live_schema: bool}
     */
    public function documentMeta(
        ?Page $page = null,
        ?Service $service = null,
        ?ServiceCategory $category = null,
        ?ServiceLocationPage $mapping = null,
        ?SubService $subService = null,
    ): array {
        $seoLocked = $page !== null && ServicePageOverrides::seoOverride($page);
        $titleLocked = $page !== null && ServicePageOverrides::titleOverride($page);
        $preferLiveSchema = $page !== null
            && $page->page_source === 'generated'
            && ! ServicePageOverrides::geoOverride($page);

        if ($mapping !== null && $service !== null && $mapping->pincode instanceof PinCode) {
            $pin = $mapping->pincode;

            return [
                'title' => $titleLocked && filled($page?->title) ? (string) $page->title : $this->locationHeadline($service, $pin),
                'meta_title' => $seoLocked && filled($page?->meta_title)
                    ? (string) $page->meta_title
                    : $this->locationMetaTitle($service, $pin),
                'meta_description' => $seoLocked && filled($page?->meta_description)
                    ? (string) $page->meta_description
                    : $this->locationMetaDescription($service, $pin),
                'prefer_live_schema' => $preferLiveSchema,
            ];
        }

        if ($subService !== null) {
            $subService->loadMissing('seo');

            return [
                'title' => $titleLocked && filled($page?->title) ? (string) $page->title : $this->subServiceHeadline($subService),
                'meta_title' => $seoLocked && filled($page?->meta_title)
                    ? (string) $page->meta_title
                    : $this->subServiceMetaTitle($subService),
                'meta_description' => $seoLocked && filled($page?->meta_description)
                    ? (string) $page->meta_description
                    : (filled($subService->seo?->meta_description) ? (string) $subService->seo->meta_description : null),
                'prefer_live_schema' => $preferLiveSchema,
            ];
        }

        if ($service !== null) {
            return [
                'title' => $titleLocked && filled($page?->title) ? (string) $page->title : $this->serviceHeadline($service),
                'meta_title' => $seoLocked && filled($page?->meta_title)
                    ? (string) $page->meta_title
                    : $this->serviceMetaTitle($service),
                'meta_description' => $seoLocked && filled($page?->meta_description)
                    ? (string) $page->meta_description
                    : $this->serviceMetaDescription($service),
                'prefer_live_schema' => $preferLiveSchema,
            ];
        }

        if ($category !== null) {
            return [
                'title' => $titleLocked && filled($page?->title) ? (string) $page->title : $this->categoryHeadline($category),
                'meta_title' => $seoLocked && filled($page?->meta_title)
                    ? (string) $page->meta_title
                    : $this->categoryMetaTitle($category),
                'meta_description' => $seoLocked && filled($page?->meta_description)
                    ? (string) $page->meta_description
                    : $this->categoryMetaDescription($category),
                'prefer_live_schema' => $preferLiveSchema,
            ];
        }

        return [
            'title' => (string) ($page?->title ?? config('medca.brand_name')),
            'meta_title' => (string) ($page?->meta_title ?? $page?->title ?? config('medca.brand_name')),
            'meta_description' => filled($page?->meta_description) ? (string) $page->meta_description : null,
            'prefer_live_schema' => false,
        ];
    }
}
