<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\Operations\ServiceLocationMatrixReconciler;
use Illuminate\Console\Command;

class ReconcileServiceLocationMatrixCommand extends Command
{
    protected $signature = 'medca:reconcile-service-location-matrix
                            {--service= : Limit to one service_code}
                            {--service-ids= : Comma-separated numeric service IDs}
                            {--refresh : Re-provision existing location pages (slower)}';

    protected $description = 'Reconcile service_pincodes pivot with location pages, indexability, and internal links';

    public function handle(ServiceLocationMatrixReconciler $reconciler): int
    {
        $refreshExisting = (bool) $this->option('refresh');

        if ($rawIds = $this->option('service-ids')) {
            $ids = array_values(array_unique(array_filter(array_map(
                static fn (mixed $id): int => (int) $id,
                explode(',', (string) $rawIds)
            ), static fn (int $id): bool => $id > 0)));

            if ($ids === []) {
                $this->error('No valid service IDs provided.');

                return self::FAILURE;
            }

            $report = $reconciler->reconcileMany($ids, refreshExisting: $refreshExisting);
        } else {
            $service = null;
            if ($code = $this->option('service')) {
                $service = Service::query()->where('service_code', $code)->first();
                if ($service === null) {
                    $this->error("Service not found: {$code}");

                    return self::FAILURE;
                }
            }

            $report = $reconciler->reconcile($service, refreshExisting: $refreshExisting);
        }

        $this->table(
            ['Metric', 'Count'],
            collect($report)->except('issues')->map(fn ($v, $k) => [$k, $v])->values()->all()
        );

        if ($report['issues'] !== []) {
            $this->warn('Issues:');
            foreach ($report['issues'] as $issue) {
                $this->line('  - '.$issue);
            }
        }

        $this->info('Matrix reconciliation complete.');

        return self::SUCCESS;
    }
}
