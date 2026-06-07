<?php

namespace App\Console\Commands;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Seo\UnifiedJsonLdGraphBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GeoEntityValidationReportCommand extends Command
{
    protected $signature = 'medca:geo-entity-report {--output= : Markdown output path}';

    protected $description = 'Audit GEO entity coverage across services and location pages';

    public function handle(UnifiedJsonLdGraphBuilder $graphBuilder): int
    {
        $services = Service::query()->publicListing()->with(['pincodes', 'locationPages.pincode'])->get();
        $locations = ServiceLocationPage::query()
            ->with(['service', 'pincode.landmarks', 'pincode.hospitals', 'pincode.locationFaqs', 'pincode.nearbyAreas', 'page'])
            ->get();

        $serviceGeo = [];
        $missingServiceFaqs = 0;

        foreach ($services as $service) {
            $graph = $graphBuilder->buildServiceGraph($service);
            $types = collect($graph['@graph'] ?? [])->pluck('@type')->flatten()->unique()->values()->all();
            $hasFaq = collect($graph['@graph'] ?? [])->contains(fn ($n) => ($n['@type'] ?? '') === 'FAQPage');
            if (! $hasFaq) {
                $missingServiceFaqs++;
            }
            $serviceGeo[] = [
                'code' => $service->service_code,
                'graph_nodes' => count($graph['@graph'] ?? []),
                'types' => $types,
                'has_faq' => $hasFaq,
                'pincode_count' => $service->pincodes->count(),
            ];
        }

        $locationAudit = [];
        $missingHospitals = 0;
        $missingLandmarks = 0;
        $missingFaqs = 0;
        $missingAreaServed = 0;

        foreach ($locations as $mapping) {
            $pin = $mapping->pincode;
            if ($pin === null || $mapping->service === null) {
                continue;
            }

            $graph = $graphBuilder->buildLocationGraph($mapping->service, $pin, $mapping);
            $nodes = collect($graph['@graph'] ?? []);
            $hasArea = $nodes->contains(fn ($n) => ($n['@id'] ?? '') === $mapping->publicUrl().'#geographic-area');
            $hospitalCount = $nodes->filter(fn ($n) => ($n['@type'] ?? '') === 'Hospital')->count();
            $landmarkCount = $nodes->filter(fn ($n) => str_contains((string) ($n['@id'] ?? ''), '#landmark-'))->count();
            $faqCount = collect($nodes->firstWhere('@type', 'FAQPage')['mainEntity'] ?? [])->count();

            if ($pin->hospitals->isEmpty()) {
                $missingHospitals++;
            }
            if ($pin->landmarks->isEmpty()) {
                $missingLandmarks++;
            }
            if ($pin->locationFaqs->isEmpty() && $faqCount === 0) {
                $missingFaqs++;
            }
            if (! $hasArea) {
                $missingAreaServed++;
            }

            $locationAudit[] = [
                'url' => $mapping->publicUrl(),
                'indexable' => $mapping->isPubliclyIndexable(),
                'hospitals_in_data' => $pin->hospitals->count(),
                'hospitals_in_graph' => $hospitalCount,
                'landmarks_in_data' => $pin->landmarks->count(),
                'landmarks_in_graph' => $landmarkCount,
                'faq_entities' => $faqCount,
                'has_geographic_area' => $hasArea,
            ];
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'services_with_geo_entities' => count($serviceGeo),
            'location_pages_with_geo_entities' => count($locationAudit),
            'services_missing_faq_graph' => $missingServiceFaqs,
            'locations_missing_hospitals_data' => $missingHospitals,
            'locations_missing_landmarks_data' => $missingLandmarks,
            'locations_missing_faqs' => $missingFaqs,
            'locations_missing_area_served' => $missingAreaServed,
            'services' => $serviceGeo,
            'locations_sample' => array_slice($locationAudit, 0, 25),
        ];

        $path = $this->option('output') ?: base_path('docs/GEO-ENTITY-VALIDATION-REPORT.md');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, "# GEO Entity Validation Report\n\n```json\n".json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n```\n");

        $this->info("GEO entity report written to {$path}");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Published services', count($serviceGeo)],
                ['Location pages', count($locationAudit)],
                ['Services missing FAQ in graph', $missingServiceFaqs],
                ['Locations missing hospital data', $missingHospitals],
                ['Locations missing landmark data', $missingLandmarks],
                ['Locations missing FAQs', $missingFaqs],
            ]
        );

        return self::SUCCESS;
    }
}
