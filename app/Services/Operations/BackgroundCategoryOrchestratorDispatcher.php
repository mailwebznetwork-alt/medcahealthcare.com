<?php

namespace App\Services\Operations;

use App\Models\ServiceCategory;

/**
 * @deprecated Use CatalogOperationsCascade — kept for call-site compatibility.
 */
final class BackgroundCategoryOrchestratorDispatcher
{
    public function __construct(
        private readonly CatalogOperationsCascade $cascade,
    ) {}

    public function dispatch(int $categoryId): void
    {
        if ($categoryId <= 0) {
            return;
        }

        $category = ServiceCategory::query()->find($categoryId);
        if ($category === null) {
            return;
        }

        $serviceIds = app(ServicePincodeCoverageService::class)
            ->propagateCategoryToServices($category);

        $this->cascade->afterCategorySaved($category, $serviceIds);
    }
}
