<?php

namespace App\Livewire\Operations;

use App\Models\Service;
use App\Services\MasterSpec\ContentHealthService;
use App\Services\MasterSpec\EntityGraphAuditService;
use App\Services\MasterSpec\ProgrammaticSeoQualityScorer;
use App\Services\MasterSpec\ThinContentRules;
use Illuminate\View\View;
use Livewire\Component;

class ContentHealthDashboard extends Component
{
    /** @var array<string, mixed> */
    public array $health = [];

    /** @var array<string, mixed> */
    public array $graphSummary = [];

    /** @var array{average: float, low_quality_count: int, samples: list<array{code: string, score: int, gaps: list<string>}>} */
    public array $seoQuality = ['average' => 0, 'low_quality_count' => 0, 'samples' => []];

    public function mount(ContentHealthService $health, EntityGraphAuditService $graph, ProgrammaticSeoQualityScorer $seoScorer): void
    {
        $this->refreshMetrics($health, $graph, $seoScorer);
    }

    public function refresh(ContentHealthService $health, EntityGraphAuditService $graph, ProgrammaticSeoQualityScorer $seoScorer): void
    {
        $this->refreshMetrics($health, $graph, $seoScorer);
    }

    public function render(ThinContentRules $thinContentRules, ProgrammaticSeoQualityScorer $seoScorer): View
    {
        $thinSamples = Service::query()
            ->where('is_active', true)
            ->orderBy('service_code')
            ->limit(200)
            ->get()
            ->filter(fn (Service $service): bool => $thinContentRules->isThinService($service))
            ->take(15)
            ->values();

        $lowScoreSamples = collect($this->seoQuality['samples'] ?? [])
            ->map(function (array $sample) use ($seoScorer): array {
                $service = Service::query()->where('service_code', $sample['code'])->first();
                $gaps = $service ? $seoScorer->scoreService($service)['gaps'] : [];

                return array_merge($sample, ['gaps' => $gaps]);
            })
            ->values();

        return view('livewire.operations.content-health-dashboard', [
            'thinSamples' => $thinSamples,
            'lowScoreSamples' => $lowScoreSamples,
        ]);
    }

    private function refreshMetrics(ContentHealthService $health, EntityGraphAuditService $graph, ProgrammaticSeoQualityScorer $seoScorer): void
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

        $summary = $seoScorer->catalogSummary();
        $this->seoQuality = [
            'average' => $summary['average'],
            'low_quality_count' => $summary['low_quality_count'],
            'samples' => collect($summary['samples'])
                ->map(fn (array $sample): array => [
                    'code' => $sample['code'],
                    'score' => $sample['score'],
                    'gaps' => [],
                ])
                ->all(),
        ];
    }
}
