<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Models\Vacancy;

class CareersJobDetailPageResolver
{
    public function resolveFor(Vacancy $vacancy): ?Page
    {
        if ($vacancy->detail_page_id !== null) {
            $linked = Page::query()
                ->whereKey($vacancy->detail_page_id)
                ->where('is_active', true)
                ->first();

            if ($linked !== null) {
                return $linked;
            }
        }

        $slug = (string) config('careers.job_detail_page_slug', '');

        if ($slug === '') {
            return null;
        }

        return Page::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }
}
