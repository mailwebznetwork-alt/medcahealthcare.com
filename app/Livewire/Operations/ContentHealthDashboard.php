<?php

namespace App\Livewire\Operations;

use App\Models\Service;
use App\Services\MasterSpec\ContentHealthService;
use App\Services\MasterSpec\EntityGraphAuditService;
use Illuminate\View\View;
use Livewire\Component;

class ContentHealthDashboard extends Component
{
    /** @var array<string, mixed> */
    public array $health = [];

    /** @var array<string, mixed> */
    public array $graphSummary = [];

    public function mount(ContentHealthService $health, EntityGraphAuditService $graph, \App\Services\MasterSpec\ProgrammaticSeoQualityScorer $seoScorer): void
    {
        $this->health = $health->report();
        $audit = $graph->audit();
        $this->graphSummary = [
            'orphan_services' => $audit['orphan_services'],
            'services_without_pincodes' => $audit['services_without_pincodes'],
            'services_without_seo' => $audit['services_without_seo'],
            'pincodes_without_zone' => $audit['pincodes_without_zone'],
            'location_pages_orphan' => $audit['location_pages_orphan'],
        ];
        $this->seoQuality = $seoScorer->catalogSummary();
    }

    /** @var array{average: float, low_quality_count: int, samples: list<array{code: string, score: int}>} */
    public array $seoQuality = ['average' => 0, 'low_quality_count' => 0, 'samples' => []];

    public function refresh(ContentHealthService $health, EntityGraphAuditService $graph, \App\Services\MasterSpec\ProgrammaticSeoQualityScorer $seoScorer): void
    {
        $this->health = $health->report();
        $audit = $graph->audit();
        $this->graphSummary = [
            'orphan_services' => $audit['orphan_services'],
            'services_without_pincodes' => $audit['services_without_pincodes'],
            'services_without_seo' => $audit['services_without_seo'],
            'pincodes_without_zone' => $audit['pincodes_without_zone'],
            'location_pages_orphan' => $audit['location_pages_orphan'],
        ];
        $this->seoQuality = $seoScorer->catalogSummary();
    }

    public function render(): View
    {
        $thinSamples = Service::query()
            ->where('is_active', true)
            ->orderBy('service_code')
            ->limit(200)
            ->get()
            ->filter(function (Service $service): bool {
                $words = str_word_count((string) ($service->description ?? '').' '.(string) ($service->short_summary ?? ''));

                return $words < 40;
            })
            ->take(15)
            ->values();

        return view('livewire.operations.content-health-dashboard', [
            'thinSamples' => $thinSamples,
        ]);
    }
}
