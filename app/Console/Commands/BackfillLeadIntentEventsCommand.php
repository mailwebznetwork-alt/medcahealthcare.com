<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\MarketingClickEvent;
use App\Services\Marketing\LeadIntent\LeadIntentRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillLeadIntentEventsCommand extends Command
{
    protected $signature = 'lead-intent:backfill {--days=90 : Number of days of marketing clicks to process}';

    protected $description = 'Backfill lead_intent_events from marketing_click_events and existing leads';

    public function handle(LeadIntentRecorder $recorder): int
    {
        if (! Schema::hasTable('lead_intent_events')) {
            $this->error('Run migrations first.');

            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));
        $from = now()->subDays($days);
        $clicks = 0;
        $leads = 0;

        if (Schema::hasTable('marketing_click_events')) {
            MarketingClickEvent::query()
                ->where('occurred_at', '>=', $from)
                ->orderBy('id')
                ->chunkById(200, function ($events) use ($recorder, &$clicks): void {
                    foreach ($events as $event) {
                        if ($recorder->recordFromMarketingClick($event) !== null) {
                            $clicks++;
                        }
                    }
                });
        }

        if (Schema::hasTable('leads')) {
            Lead::query()
                ->where('created_at', '>=', $from)
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($recorder, &$leads): void {
                    foreach ($rows as $lead) {
                        if ($recorder->recordFromLead($lead) !== null) {
                            $leads++;
                        }
                    }
                });
        }

        $this->info("Backfill complete: {$clicks} click intents, {$leads} lead form intents.");

        return self::SUCCESS;
    }
}
