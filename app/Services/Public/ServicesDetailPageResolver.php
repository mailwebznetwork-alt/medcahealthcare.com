<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Models\Service;
use App\Services\Operations\ServiceDetailPageProvisioner;

class ServicesDetailPageResolver
{
    public function __construct(
        private readonly ServiceDetailPageProvisioner $detailPageProvisioner,
    ) {}

    public function resolveFor(Service $service): ?Page
    {
        if ($service->detail_page_id !== null) {
            $linked = Page::query()
                ->whereKey($service->detail_page_id)
                ->where('is_active', true)
                ->first();

            if ($linked !== null) {
                return $linked;
            }
        }

        $patternSlug = $this->detailPageProvisioner->suggestedSlug($service);

        if ($patternSlug !== '') {
            $byPattern = Page::query()
                ->where('slug', $patternSlug)
                ->where('is_active', true)
                ->first();

            if ($byPattern !== null) {
                if ($service->detail_page_id !== $byPattern->id) {
                    $service->forceFill(['detail_page_id' => $byPattern->id])->saveQuietly();
                }

                return $byPattern;
            }
        }

        $slug = (string) config('public_pages.service_detail_page_slug', '');

        if ($slug === '') {
            return null;
        }

        return Page::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }
}
