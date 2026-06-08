<?php

namespace App\Console\Commands;

use App\Models\PinCode;
use App\Models\Service;
use App\Services\Operations\ServiceMasterOrchestrator;
use App\Services\Seo\LocalityContextResolver;
use Illuminate\Console\Command;

class ExpandServiceLocationPagesCommand extends Command
{
    protected $signature = 'services:expand-location-pages
                            {--service= : Limit to one service_code}
                            {--dry-run : Preview pincode assignments without syncing}';

    protected $description = 'Map eligible pincodes to published services and provision location pages';

    public function handle(ServiceMasterOrchestrator $orchestrator): int
    {
        $config = config('services_master.pincode_expansion', []);
        $cityFilter = (string) ($config['city_filter'] ?? app(LocalityContextResolver::class)->primaryCity() ?? '');
        $requireServiceable = (bool) ($config['require_serviceable'] ?? true);
        $requireActive = (bool) ($config['require_active'] ?? true);

        $pinQuery = PinCode::query();
        if ($requireActive) {
            $pinQuery->where('is_active', true);
        }
        if ($requireServiceable) {
            $pinQuery->where('is_serviceable', true);
        }
        if ($cityFilter !== '') {
            $pinQuery->where('city', 'like', '%'.$cityFilter.'%');
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

            $attachable = app(\App\Services\Governance\MappingProtectionService::class)
                ->filterAttachablePinIds($service, $pinIds, 'sync');
            if ($attachable !== []) {
                $service->pincodes()->syncWithoutDetaching($attachable);
            }
            $orchestrator->sync($service->fresh(['pincodes', 'seo', 'faqs', 'schema']));
            $this->line("Synced: {$service->service_code}");
        }

        $this->info('Location page expansion complete.');

        return self::SUCCESS;
    }
}
