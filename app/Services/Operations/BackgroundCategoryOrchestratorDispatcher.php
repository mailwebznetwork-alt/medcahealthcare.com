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

        $artisan = escapeshellarg(base_path('artisan'));
        $php = PHP_BINARY;
        $command = 'sleep 2 && '.$php.' '.$artisan.' medca:sync-category-master '.(int) $categoryId;

        Process::path(base_path())
            ->timeout(3600)
            ->start(['bash', '-c', $command]);
    }
}
