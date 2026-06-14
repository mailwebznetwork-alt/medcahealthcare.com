<?php

namespace App\Services\MasterSpec;

use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\MasterSpec\QuickAnswerGenerator;

class ContentHealthService
{
    public function __construct(
        private readonly QuickAnswerGenerator $quickAnswers,
        private readonly ThinContentRules $thinContentRules,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $thinServices = Service::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (Service $service): bool => $this->thinContentRules->isThinService($service))
            ->count();

        $missingQuickAnswer = Service::query()
            ->where('is_active', true)
            ->whereNull('quick_answer')
            ->count();

        $missingAiSummary = Service::query()
            ->where('is_active', true)
            ->whereNull('ai_summary')
            ->count();

        $missingSchemaPages = Page::query()
            ->where('is_active', true)
            ->whereNull('schema_json')
            ->count();

        $thinLocations = ServiceLocationPage::query()
            ->where('is_indexable', true)
            ->with('page')
            ->get()
            ->filter(fn (ServiceLocationPage $row): bool => $this->thinContentRules->isThinLocation($row))
            ->count();

        $pendingMedical = Service::query()
            ->whereIn('medical_review_status', ['draft', 'pending_medical'])
            ->count();

        return [
            'thin_services' => $thinServices,
            'missing_quick_answer' => $missingQuickAnswer,
            'missing_ai_summary' => $missingAiSummary,
            'pages_missing_schema_json' => $missingSchemaPages,
            'thin_indexable_locations' => $thinLocations,
            'pending_medical_review' => $pendingMedical,
            'recommendations' => $this->recommendations($thinServices, $missingQuickAnswer, $pendingMedical),
        ];
    }

    /**
     * @return list<string>
     */
    private function recommendations(int $thin, int $noQuick, int $pendingMedical): array
    {
        $items = [];

        if ($thin > 0) {
            $items[] = "Expand {$thin} services with short descriptions (target 80+ words).";
        }

        if ($noQuick > 0) {
            $items[] = "Run medca:fill-quick-answers or import quick_answer column for {$noQuick} services.";
        }

        if ($pendingMedical > 0) {
            $items[] = "{$pendingMedical} catalog records await medical review before E-E-A-T publication.";
        }

        return $items;
    }
}
