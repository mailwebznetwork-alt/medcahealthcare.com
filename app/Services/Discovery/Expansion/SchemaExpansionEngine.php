<?php

namespace App\Services\Discovery\Expansion;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use App\Services\Operations\ServiceSchemaGenerator;
use App\Services\Seo\CategoryJsonLdBuilder;
use App\Services\Seo\SubServiceJsonLdBuilder;
use App\Services\Seo\UnifiedJsonLdGraphBuilder;

/**
 * Keeps JSON-LD synchronized across all generated page types.
 */
class SchemaExpansionEngine
{
    public function __construct(
        private readonly UnifiedJsonLdGraphBuilder $serviceGraph,
        private readonly CategoryJsonLdBuilder $categoryGraph,
        private readonly SubServiceJsonLdBuilder $subServiceGraph,
        private readonly ServiceSchemaGenerator $serviceSchemaGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forCategory(ServiceCategory $category): array
    {
        return $this->categoryGraph->buildGraph($category);
    }

    /**
     * @return array<string, mixed>
     */
    public function forService(Service $service): array
    {
        return $this->serviceGraph->buildServiceGraph($service);
    }

    /**
     * @return array<string, mixed>
     */
    public function forSubService(SubService $sub): array
    {
        return $this->subServiceGraph->buildGraph($sub);
    }

    /**
     * @return array<string, mixed>
     */
    public function forLocation(Service $service, PinCode $pin, ServiceLocationPage $mapping): array
    {
        return $this->serviceSchemaGenerator->buildLocationGraph($service, $pin, $mapping);
    }
}
