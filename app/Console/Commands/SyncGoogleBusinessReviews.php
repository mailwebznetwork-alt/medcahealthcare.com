<?php

namespace App\Console\Commands;

use App\Services\Integrations\GoogleBusinessProfileService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-google-business-reviews')]
#[Description('Sync Google Business Profile reviews into local storage')]
class SyncGoogleBusinessReviews extends Command
{
    public function __construct(private readonly GoogleBusinessProfileService $googleBusinessProfileService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->googleBusinessProfileService->syncReviews();

        if ($result['success']) {
            $this->info((string) $result['message'].' ('.$result['count'].')');

            return self::SUCCESS;
        }

        $this->error((string) $result['message']);

        return self::FAILURE;
    }
}
