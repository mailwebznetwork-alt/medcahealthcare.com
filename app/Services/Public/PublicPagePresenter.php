<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\Vacancy;
use App\Services\UserLocationService;

class PublicPagePresenter
{
    public function __construct(
        private readonly UserLocationService $location,
    ) {}

    /**
     * Blade variables injected into all blocks when rendering a CMS page.
     *
     * @return array<string, mixed>
     */
    public function variablesFor(Page $page): array
    {
        return match ($page->slug) {
            'home' => [
                'nearYouServices' => $this->localizedServices(limit: 6),
                'nearYouPayload' => $this->nearYouPayload(),
            ],
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
                'publishedServices' => $this->localizedServices(),
                'nearYouServices' => $this->localizedServices(limit: 6),
            ],
            default => [],
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, Service>
     */
    public function localizedServices(?string $pincode = null, int $limit = 0): \Illuminate\Support\Collection
    {
        $pincode ??= $this->location->currentPincode();
        if ($pincode === null) {
            return collect();
        }

        $query = Service::query()
            ->localizedListing($pincode)
            ->with(['seo', 'pincodes']);

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function nearYouPayload(): array
    {
        $pincode = $this->location->currentPincode();
        $record = $this->location->currentPinCodeRecord();

        return [
            'pincode' => $pincode,
            'pinCodeRecord' => $record,
            'services' => $this->localizedServices($pincode, 6),
            'locationRequired' => $pincode === null,
        ];
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
