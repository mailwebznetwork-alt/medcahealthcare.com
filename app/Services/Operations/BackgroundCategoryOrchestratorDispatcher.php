<?php

namespace App\Services\Operations;

use Illuminate\Support\Facades\Process;

final class BackgroundCategoryOrchestratorDispatcher
{
    public function dispatch(int $categoryId): void
    {
        if ($categoryId <= 0) {
            return;
        }

        Process::path(base_path())
            ->timeout(3600)
            ->start([
                PHP_BINARY,
                base_path('artisan'),
                'medca:sync-category-master',
                (string) $categoryId,
            ]);
    }
}
