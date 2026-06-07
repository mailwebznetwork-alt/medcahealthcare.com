<?php

namespace App\Services\Governance;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use Illuminate\Support\Collection;

/**
 * Category → Service → Sub Service hierarchy without relationship conflicts.
 */
class CatalogHierarchyService
{
    /**
     * @return array{
     *   category: array<string, mixed>,
     *   services: list<array<string, mixed>>,
     *   sub_services: list<array<string, mixed>>
     * }
     */
    public function treeForCategory(ServiceCategory $category): array
    {
        $category->loadMissing([
            'services' => fn ($q) => $q->publicListing()->with(['subServices' => fn ($sq) => $sq->publicListing()]),
        ]);

        return [
            'category' => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'is_featured' => (bool) $category->is_featured,
            ],
            'services' => $category->services->map(fn (Service $service): array => [
                'id' => $service->id,
                'code' => $service->service_code,
                'title' => $service->title,
                'is_featured' => (bool) $service->is_featured,
                'is_top_rated' => (bool) $service->is_top_rated,
                'sub_service_count' => $service->subServices->count(),
            ])->values()->all(),
            'sub_services' => $category->services
                ->flatMap(fn (Service $service): Collection => $service->subServices->map(fn (SubService $sub): array => [
                    'id' => $sub->id,
                    'parent_service_code' => $service->service_code,
                    'code' => $sub->sub_service_code,
                    'title' => $sub->title,
                    'is_featured' => (bool) $sub->is_featured,
                    'standalone_service_id' => $sub->standalone_service_id,
                ]))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<string>
     */
    public function detectConflicts(): array
    {
        $issues = [];

        SubService::query()
            ->whereNotNull('standalone_service_id')
            ->with(['service', 'standaloneService'])
            ->each(function (SubService $sub) use (&$issues): void {
                if ($sub->standalone_service_id === $sub->service_id) {
                    $issues[] = "sub_service:{$sub->sub_service_code} cannot promote to its own parent service";
                }
            });

        return $issues;
    }
}
