<?php

namespace App\Console\Commands;

use App\Models\PinCode;
use App\Models\Service;
use App\Services\Operations\ServiceMasterOrchestrator;
use App\Services\Seo\LocalityContextResolver;
use Illuminate\Console\Command;

class ExpandServiceLocationPincodesCommand extends Command
{
    protected $signature = 'services:expand-location-pincodes
                            {--service= : Limit to one service_code}
                            {--dry-run : Preview pincode assignments without syncing}';

    protected $description = 'Map eligible pincodes to published services and provision location pages';

    public function handle(ServiceMasterOrchestrator $orchestrator): int
    {
        $city = (string) (config('services_master.pincode_expansion.city_filter') ?? app(LocalityContextResolver::class)->primaryCity() ?? '');
        $requireServiceable = (bool) config('services_master.pincode_expansion.require_serviceable', true);
        $requireActive = (bool) config('services_master.pincode_expansion.require_active', true);

        $pinQuery = PinCode::query();
        if ($city !== '') {
            $pinQuery->where('city', 'like', '%'.$city.'%');
        }
        if ($requireActive) {
            $pinQuery->where('is_active', true);
        }
        if ($requireServiceable) {
            $pinQuery->where('is_serviceable', true);
        }

        $pinIds = $pinQuery->pluck('id')->all();
        if ($pinIds === []) {
            $this->warn('No eligible pincodes found for expansion.');

            return self::FAILURE;
        }

        $serviceQuery = Service::query()->publicListing();
        if ($code = $this->option('service')) {
            $serviceQuery->where('service_code', $code);
        }

        $services = $serviceQuery->get();
        if ($services->isEmpty()) {
            $this->warn('No published public services found.');

            return self::FAILURE;
        }

        $this->info(sprintf('Expanding %d service(s) across %d pincode(s).', $services->count(), count($pinIds)));

        foreach ($services as $service) {
            if ($this->option('dry-run')) {
                $this->line("DRY RUN: {$service->service_code} ← ".count($pinIds).' pincodes');

                continue;
            }

            $service->pincodes()->syncWithoutDetaching($pinIds);
            $orchestrator->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));
            $this->line("Synced {$service->service_code}");
        }

        $this->info('Pincode expansion complete.');

        return self::SUCCESS;
    }
}
