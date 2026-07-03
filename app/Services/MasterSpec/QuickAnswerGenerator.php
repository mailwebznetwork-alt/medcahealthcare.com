<?php

namespace App\Services\MasterSpec;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use Illuminate\Database\Eloquent\Model;

/**
 * Generates concise direct-answer strings for AEO / featured snippets from catalog source data.
 */
class QuickAnswerGenerator
{
    public function generateForService(Service $service): string
    {
        if (filled($service->quick_answer)) {
            return trim((string) $service->quick_answer);
        }

        $title = method_exists($service, 'publicListingTitle')
            ? $service->publicListingTitle()
            : (string) ($service->title ?? '');
        $area = config('medca.default_city', 'Bangalore');
        $summary = trim((string) ($service->short_summary ?? $service->description ?? ''));

        if ($summary !== '') {
            $sentence = preg_split('/(?<=[.!?])\s+/', $summary, 2)[0] ?? $summary;

            return rtrim($sentence, '.').'.';
        }

        $brand = (string) config('medca.brand_name', 'Medca Healthcare');

        return "{$title} is available across {$area} through {$brand} with doctor-led home healthcare and supervised nursing support.";
    }

    public function generateForCategory(ServiceCategory $category): string
    {
        if (filled($category->quick_answer)) {
            return trim((string) $category->quick_answer);
        }

        $name = (string) ($category->name ?? $category->title ?? 'Service category');
        $city = config('medca.default_city', 'Bangalore');

        $brand = (string) config('medca.brand_name', 'Medca Healthcare');

        return "{$brand} provides {$name} across {$city} with hospital-grade home care, clinical supervision, and family-centred support.";
    }

    public function generateForSubService(SubService $subService): string
    {
        if (filled($subService->quick_answer)) {
            return trim((string) $subService->quick_answer);
        }

        $title = $subService->publicListingTitle();
        $parent = $subService->relationLoaded('service')
            ? (string) ($subService->service?->title ?? 'medical laboratory services')
            : (string) ($subService->service()->value('title') ?? 'medical laboratory services');
        $city = config('medca.default_city', 'Karnataka');

        $brand = (string) config('medca.brand_name', 'Medca Healthcare');

        return "{$title} is a specialized {$parent} offering from {$brand} in {$city}.";
    }

    public function fillIfEmpty(Model $entity): void
    {
        if (! filled($entity->quick_answer)) {
            $answer = match (true) {
                $entity instanceof Service => $this->generateForService($entity),
                $entity instanceof ServiceCategory => $this->generateForCategory($entity),
                $entity instanceof SubService => $this->generateForSubService($entity),
                default => null,
            };

            if ($answer !== null) {
                $entity->forceFill(['quick_answer' => $answer]);
            }
        }
    }
}
