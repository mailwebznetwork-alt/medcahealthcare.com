<?php

namespace App\Services\Operations;

use Illuminate\Support\Facades\Process;

final class BackgroundCategoryPincodePropagationDispatcher
{
    public function dispatch(int $categoryId): void
    {
        if ($categoryId <= 0) {
            return;
        }

        $artisan = escapeshellarg(base_path('artisan'));
        $php = PHP_BINARY;
        $command = 'sleep 1 && '.$php.' '.$artisan.' medca:propagate-category-pincodes '.(int) $categoryId;

        Process::path(base_path())
            ->timeout(3600)
            ->start(['bash', '-c', $command]);
    }
}
