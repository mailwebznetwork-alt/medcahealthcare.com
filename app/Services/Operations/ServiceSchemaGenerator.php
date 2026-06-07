<?php

namespace App\Services\Operations;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Seo\UnifiedJsonLdGraphBuilder;

class ServiceSchemaGenerator
{
    public function __construct(
        private readonly UnifiedJsonLdGraphBuilder $graphBuilder,
    ) {}

    /**
     * Build validated JSON-LD @graph for a service (master source).
     *
     * @return array<string, mixed>
     */
    public function buildGraph(Service $service): array
    {
        return $this->graphBuilder->buildServiceGraph($service);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildLocationGraph(Service $service, PinCode $pin, ServiceLocationPage $mapping): array
    {
        return $this->graphBuilder->buildLocationGraph($service, $pin, $mapping);
    }

    public function generateAndPersist(Service $service): void
    {
        $graph = $this->buildGraph($service);

        $service->schema()->updateOrCreate(
            ['service_id' => $service->id],
            [
                'schema_type' => 'ServiceGraph',
                'schema_json' => $graph,
            ]
        );
    }
}
