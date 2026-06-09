<?php

namespace App\Console\Commands;

use App\Services\Operations\ServicePincodeAutoMapper;
use Illuminate\Console\Command;

class ExpandServiceLocationPincodesCommand extends Command
{
    protected $signature = 'services:expand-location-pincodes
                            {--service= : Limit to one service_code}
                            {--dry-run : Preview pincode assignments without syncing}';

    protected $description = 'Map eligible pincodes to published services and provision location pages';

    public function handle(ServicePincodeAutoMapper $mapper): int
    {
        $serviceCode = $this->option('service');

        if ($this->option('dry-run')) {
            $estimate = $mapper->estimate($serviceCode);
            if ($estimate['pincodes_eligible'] === 0) {
                $this->warn($estimate['message']);

                return self::FAILURE;
            }
            if ($estimate['services_eligible'] === 0) {
                $this->warn($estimate['message']);

                return self::FAILURE;
            }

            foreach ($estimate['service_codes'] as $code) {
                $this->line("DRY RUN: {$code} ← {$estimate['pincodes_eligible']} pincodes");
            }

            return self::SUCCESS;
        }

        $result = $mapper->map($serviceCode);

        if ($result['pincodes_eligible'] === 0 || ! $result['mapped']) {
            $this->warn($result['message']);

            return self::FAILURE;
        }

        $this->info($result['message']);
        $this->info('Pincode expansion complete.');

        return self::SUCCESS;
    }
}
