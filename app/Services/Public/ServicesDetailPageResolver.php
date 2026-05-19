<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Models\Service;

class ServicesDetailPageResolver
{
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
