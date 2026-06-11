<?php

namespace App\Services\Marketing\Attribution;

/**
 * Resolved landing-page attribution context for a single request.
 */
final class LandingContext
{
    public function __construct(
        public readonly string $landingPagePath,
        public readonly ?int $pageId = null,
        public readonly ?int $serviceId = null,
        public readonly ?int $pinCodeId = null,
        public readonly ?int $serviceLocationPageId = null,
        public readonly ?int $serviceCategoryId = null,
        public readonly ?int $subServiceId = null,
    ) {}

    /**
     * @return array<string, int|string|null>
     */
    public function entityAttributes(): array
    {
        return array_filter([
            'page_id' => $this->pageId,
            'service_id' => $this->serviceId,
            'pin_code_id' => $this->pinCodeId,
            'service_location_page_id' => $this->serviceLocationPageId,
            'service_category_id' => $this->serviceCategoryId,
            'sub_service_id' => $this->subServiceId,
            'landing_page_path' => $this->landingPagePath,
        ], fn ($value) => $value !== null);
    }

    /**
     * FK columns shared by clicks, intents, and leads.
     *
     * @return array<string, int|null>
     */
    public function foreignKeyAttributes(): array
    {
        return array_filter([
            'page_id' => $this->pageId,
            'service_id' => $this->serviceId,
            'pin_code_id' => $this->pinCodeId,
            'service_location_page_id' => $this->serviceLocationPageId,
        ], fn ($value) => $value !== null);
    }
}
