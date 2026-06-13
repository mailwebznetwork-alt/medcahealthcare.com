<?php

namespace App\Services\MasterSpec;

use App\Models\Service;
use App\Enums\MedicalReviewStatus;

class ProgrammaticSeoQualityScorer
{
    /**
     * @return array{score: int, factors: list<string>, gaps: list<string>}
     */
    public function scoreService(Service $service): array
    {
        $service->loadMissing(['seo', 'faqs', 'pincodes']);
        $score = 0;
        $factors = [];
        $gaps = [];

        if (filled($service->short_summary) || filled($service->description)) {
            $score += 15;
            $factors[] = 'description';
        } else {
            $gaps[] = 'missing_description';
        }

        if (filled($service->quick_answer)) {
            $score += 15;
            $factors[] = 'quick_answer';
        } else {
            $gaps[] = 'missing_quick_answer';
        }

        if (filled($service->ai_summary)) {
            $score += 10;
            $factors[] = 'ai_summary';
        } else {
            $gaps[] = 'missing_ai_summary';
        }

        if ($service->faqs->count() >= 3) {
            $score += 15;
            $factors[] = 'faqs';
        } else {
            $gaps[] = 'thin_faqs';
        }

        if (filled($service->seo?->meta_title) && filled($service->seo?->meta_description)) {
            $score += 15;
            $factors[] = 'meta';
        } else {
            $gaps[] = 'missing_meta';
        }

        if ($service->pincodes->count() >= 5) {
            $score += 15;
            $factors[] = 'geo_coverage';
        } else {
            $gaps[] = 'thin_geo_coverage';
        }

        if ($service->medical_review_status === MedicalReviewStatus::Approved) {
            $score += 10;
            $factors[] = 'medical_approved';
        } else {
            $gaps[] = 'medical_not_approved';
        }

        if (filled($service->featured_media_id) || filled($service->featured_image)) {
            $score += 10;
            $factors[] = 'featured_image';
        } else {
            $gaps[] = 'missing_image';
        }

        return [
            'score' => min(100, $score),
            'factors' => $factors,
            'gaps' => $gaps,
        ];
    }

    /**
     * @return array{average: float, low_quality_count: int, samples: list<array{code: string, score: int}>}
     */
    public function catalogSummary(int $sampleLimit = 20): array
    {
        $scores = [];
        Service::query()
            ->where('is_active', true)
            ->orderBy('service_code')
            ->each(function (Service $service) use (&$scores): void {
                $scores[$service->service_code] = $this->scoreService($service)['score'];
            });

        $avg = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
        $low = collect($scores)->filter(fn (int $s): bool => $s < 60)->count();

        $samples = collect($scores)
            ->sort()
            ->take($sampleLimit)
            ->map(fn (int $score, string $code): array => ['code' => $code, 'score' => $score])
            ->values()
            ->all();

        return [
            'average' => round($avg, 1),
            'low_quality_count' => $low,
            'samples' => $samples,
        ];
    }
}
