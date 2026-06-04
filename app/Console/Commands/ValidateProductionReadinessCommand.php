<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\MarketingSetting;
use App\Models\OutboundWebhook;
use App\Models\WebhookDelivery;
use App\Services\Integrations\WhatsAppClickToChatService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ValidateProductionReadinessCommand extends Command
{
    protected $signature = 'medca:validate-production-readiness {--probe-webhooks : HTTP probe enabled webhooks (may hit real URLs)}';

    protected $description = 'Audit webhooks, WhatsApp, and GA4/Meta configuration for go-live checks';

    public function handle(WhatsAppClickToChatService $whatsApp): int
    {
        $this->info('=== Production readiness audit ===');
        $this->newLine();

        $this->auditWebhooks();
        $this->newLine();
        $this->auditWhatsApp($whatsApp);
        $this->newLine();
        $this->auditTrackingTags();

        return self::SUCCESS;
    }

    private function auditWebhooks(): void
    {
        $this->components->info('Webhooks');

        if (! Schema::hasTable('outbound_webhooks')) {
            $this->warn('  UNCONFIGURED — outbound_webhooks table missing');

            return;
        }

        $hooks = OutboundWebhook::query()->orderBy('sort_order')->orderBy('id')->get();

        if ($hooks->isEmpty()) {
            $legacy = Integration::query()->where('name', 'webhook')->where('is_enabled', true)->first();
            if ($legacy !== null && filled($legacy->getCredential('endpoint_url'))) {
                $this->line('  Legacy integration webhook: READY (endpoint configured)');
            } else {
                $this->warn('  UNCONFIGURED — no managed webhooks and no legacy webhook integration');
            }

            return;
        }

        foreach ($hooks as $webhook) {
            $status = $webhook->is_enabled ? 'enabled' : 'disabled';
            $this->line("  [{$status}] {$webhook->name} → {$webhook->target_url}");

            if (! $webhook->is_enabled) {
                $this->warn('    Classification: UNCONFIGURED (disabled)');

                continue;
            }

            $last = WebhookDelivery::query()
                ->where('outbound_webhook_id', $webhook->id)
                ->latest('id')
                ->first();

            if ($last !== null) {
                $label = $last->success ? 'READY' : 'FAILED';
                $this->line("    Last delivery: {$label} (HTTP {$last->response_status}, {$last->event_key})");
            } elseif ($this->option('probe-webhooks')) {
                $this->probeWebhook($webhook);
            } else {
                $this->line('    Classification: UNCONFIGURED (no delivery log — run with --probe-webhooks or trigger lead.created)');
            }
        }
    }

    private function probeWebhook(OutboundWebhook $webhook): void
    {
        try {
            $response = Http::timeout(min(10, (int) $webhook->timeout_seconds))
                ->withHeaders(['X-Webhook-Event' => 'healthcheck'])
                ->post($webhook->target_url, [
                    'event' => 'healthcheck',
                    'payload' => ['probe' => true],
                    'sent_at' => now()->toIso8601String(),
                ]);

            if ($response->successful()) {
                $this->info("    Probe: READY (HTTP {$response->status()})");
            } else {
                $this->error("    Probe: FAILED (HTTP {$response->status()})");
            }
        } catch (\Throwable $e) {
            $this->error('    Probe: FAILED ('.$e->getMessage().')');
        }
    }

    private function auditWhatsApp(WhatsAppClickToChatService $whatsApp): void
    {
        $this->components->info('WhatsApp');

        $integration = Integration::query()->where('name', WhatsAppClickToChatService::INTEGRATION_NAME)->first();

        if ($integration === null) {
            $this->warn('  UNCONFIGURED — WhatsApp integration not added');

            return;
        }

        $this->line('  Integration row: '.($integration->is_enabled ? 'ENABLED' : 'DISABLED'));

        $active = $whatsApp->activeNumbers();
        $this->line('  Active numbers: '.count($active));

        if ($active === []) {
            $this->warn('  NEEDS ATTENTION — no enabled click-to-chat numbers');
        } else {
            foreach ($active as $number) {
                $this->line("    • {$number->displayName} ({$number->phone})");
            }
        }

        $this->line('  Floating button: '.($whatsApp->isFloatingButtonEnabled() ? 'on' : 'off'));
        $this->line('  Primary URL: '.$whatsApp->primaryUrl());

        $classification = ($integration->is_enabled && $active !== []) ? 'READY' : 'NEEDS ATTENTION';
        $this->line("  Classification: {$classification}");
    }

    private function auditTrackingTags(): void
    {
        $this->components->info('GA4 & Meta');

        $settings = MarketingSetting::current();
        $ga4 = $settings->ga4_measurement_id;
        $meta = $settings->meta_pixel_id;
        $gads = $settings->google_ads_aw_id;

        if (Schema::hasTable('integrations')) {
            $google = Integration::query()->where('name', 'google_services')->where('is_enabled', true)->first();
            $metaInt = Integration::query()->where('name', 'meta_ads')->where('is_enabled', true)->first();
            if ($google && is_array($google->credentials)) {
                $ga4 = $ga4 ?: '(see google_services integration)';
            }
            if ($metaInt && is_array($metaInt->credentials)) {
                $meta = $meta ?: '(see meta_ads integration)';
            }
        }

        $this->line('  GA4 measurement ID: '.(filled($ga4) ? $ga4 : 'MISSING'));
        $this->line('  Google Ads AW ID: '.(filled($gads) ? $gads : 'optional'));
        $this->line('  Meta Pixel ID: '.(filled($meta) ? $meta : 'MISSING'));

        $ga4Ready = filled($settings->ga4_measurement_id);
        $metaReady = filled($settings->meta_pixel_id);

        $this->line('  GA4 classification: '.($ga4Ready ? 'READY (ID set — confirm DebugView in browser)' : 'NEEDS ATTENTION'));
        $this->line('  Meta classification: '.($metaReady ? 'READY (ID set — confirm Events Manager)' : 'NEEDS ATTENTION'));
    }
}
