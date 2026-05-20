<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\Vacancy;

class PublicPagePresenter
{
    /**
     * Blade variables injected into all blocks when rendering a CMS page.
     *
     * @return array<string, mixed>
     */
    public function variablesFor(Page $page): array
    {
        return match ($page->slug) {
            'careers' => [
                'vacancies' => Vacancy::query()->careersListing()->get(),
            ],
            'locations' => [
                'pinCodes' => PinCode::query()
                    ->where('is_active', true)
                    ->orderBy('city')
                    ->orderBy('pincode')
                    ->get(),
            ],
            'services' => [
                'publishedServices' => Service::query()
                    ->publicListing()
                    ->with(['seo'])
                    ->get(),
            ],
            default => [],
        };
    }

    /**
     * Blade variables for /services/{code} CMS detail pages.
     *
     * @return array<string, mixed>
     */
    public function variablesForServiceDetail(Service $service): array
    {
        $service->loadMissing(['seo', 'faqs', 'pincodes']);

        return [
            'service' => $service,
            $service->bladeVariableName() => $service,
        ];
    }

    /**
     * Blade variables for /careers/{slug} when rendered via a CMS detail page.
     *
     * @return array<string, mixed>
     */
    public function variablesForVacancyDetail(Vacancy $vacancy): array
    {
        return [
            'vacancy' => $vacancy,
        ];
    }
}
