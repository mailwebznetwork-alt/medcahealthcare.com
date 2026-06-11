<?php

namespace App\Services\Operations;

use App\Models\ServiceCategory;

final class CatalogPersistResult
{
    /**
     * @param  list<int>  $reconcileServiceIds
     */
    public function __construct(
        public readonly ServiceCategory $category,
        public readonly array $reconcileServiceIds = [],
        public readonly bool $runCategoryOrchestrator = true,
    ) {}
}
