<?php

namespace App\Services\Public;

use App\Models\Blog;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Marketing\Attribution\LandingContext;

/**
 * URL → entity resolution aligned with ServicePublicController public routes.
 * Read-only lookup; does not duplicate orchestration or page provisioning.
 */
class PublicRouteAttributionResolver
{
    public function resolveFromPath(string $pathOrUrl, ?int $visitorPinCodeId = null): LandingContext
    {
        $path = $this->normalizePath($pathOrUrl);

        if ($path === '/' || $path === '') {
            return $this->cmsPageContext($path === '' ? '/' : $path, 'home', $visitorPinCodeId);
        }

        if (str_starts_with($path, '/services/')) {
            return $this->resolveServicePath($path, $visitorPinCodeId);
        }

        if (str_starts_with($path, '/service-categories/')) {
            return $this->resolveServiceCategoryPath($path, $visitorPinCodeId);
        }

        if (str_starts_with($path, '/blog/')) {
            return $this->resolveBlogPath($path, $visitorPinCodeId);
        }

        if (str_starts_with($path, '/p/')) {
            $slug = trim(substr($path, 3), '/');

            return $this->cmsPageContext($path, $slug, $visitorPinCodeId);
        }

        $slug = ltrim($path, '/');
        if (in_array($slug, config('public_pages.root_slugs', []), true)) {
            return $this->cmsPageContext($path, $slug, $visitorPinCodeId);
        }

        return new LandingContext(
            landingPagePath: $path,
            pinCodeId: $visitorPinCodeId,
        );
    }

    private function resolveServicePath(string $path, ?int $visitorPinCodeId): LandingContext
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $code = $segments[1] ?? '';
        $service = Service::findPubliclyViewableByCode($code);

        if ($service === null) {
            return new LandingContext(landingPagePath: $path, pinCodeId: $visitorPinCodeId);
        }

        $third = $segments[2] ?? null;
        $fourth = $segments[3] ?? null;

        if ($third === 'sub' && is_string($fourth) && $fourth !== '') {
            $sub = SubService::query()
                ->where('service_id', $service->id)
                ->where('sub_service_code', $fourth)
                ->publicListing()
                ->first();

            return new LandingContext(
                landingPagePath: $path,
                pageId: $sub?->page_id ?? $service->detail_page_id,
                serviceId: $service->id,
                pinCodeId: $visitorPinCodeId,
                subServiceId: $sub?->id,
            );
        }

        if ($third !== null && $fourth !== null) {
            $normalizedPin = preg_replace('/\D/', '', $fourth) ?? '';
            if (strlen($normalizedPin) === 6) {
                $mapping = ServiceLocationPage::query()
                    ->where('service_id', $service->id)
                    ->whereHas('pincode', fn ($q) => $q->where('pincode', $normalizedPin))
                    ->with(['pincode', 'page'])
                    ->first();

                if ($mapping !== null) {
                    return new LandingContext(
                        landingPagePath: $path,
                        pageId: $mapping->page_id,
                        serviceId: $service->id,
                        pinCodeId: $mapping->pincode_id,
                        serviceLocationPageId: $mapping->id,
                    );
                }
            }
        }

        if ($third !== null && $third !== 'sub') {
            $mapping = ServiceLocationPage::query()
                ->where('service_id', $service->id)
                ->where('location_slug', $third)
                ->with(['pincode', 'page'])
                ->first();

            if ($mapping !== null && $mapping->page !== null) {
                return new LandingContext(
                    landingPagePath: $path,
                    pageId: $mapping->page_id,
                    serviceId: $service->id,
                    pinCodeId: $mapping->pincode_id ?? $visitorPinCodeId,
                    serviceLocationPageId: $mapping->id,
                );
            }
        }

        return new LandingContext(
            landingPagePath: $path,
            pageId: $service->detail_page_id,
            serviceId: $service->id,
            pinCodeId: $visitorPinCodeId,
        );
    }

    private function resolveServiceCategoryPath(string $path, ?int $visitorPinCodeId): LandingContext
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $code = $segments[1] ?? '';
        $category = ServiceCategory::findActiveByCode($code);

        if ($category === null) {
            return new LandingContext(landingPagePath: $path, pinCodeId: $visitorPinCodeId);
        }

        return new LandingContext(
            landingPagePath: $path,
            pageId: $category->page_id,
            serviceCategoryId: $category->id,
            pinCodeId: $visitorPinCodeId,
        );
    }

    private function resolveBlogPath(string $path, ?int $visitorPinCodeId): LandingContext
    {
        $slug = trim(substr($path, strlen('/blog/')), '/');
        if ($slug === '') {
            return new LandingContext(landingPagePath: $path, pinCodeId: $visitorPinCodeId);
        }

        $blog = Blog::query()->where('slug', $slug)->where('is_published', true)->first();

        return new LandingContext(
            landingPagePath: $path,
            pageId: null,
            pinCodeId: $visitorPinCodeId,
        );
    }

    private function cmsPageContext(string $path, string $slug, ?int $visitorPinCodeId): LandingContext
    {
        $page = Page::query()->where('slug', $slug)->where('is_active', true)->first();

        return new LandingContext(
            landingPagePath: $path,
            pageId: $page?->id,
            pinCodeId: $visitorPinCodeId,
        );
    }

    private function normalizePath(string $pathOrUrl): string
    {
        $path = $pathOrUrl;
        if (str_contains($pathOrUrl, '://')) {
            $parsed = parse_url($pathOrUrl);
            $path = is_array($parsed) ? (string) ($parsed['path'] ?? '/') : '/';
        }

        $path = '/'.ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';

        return $path;
    }
}
