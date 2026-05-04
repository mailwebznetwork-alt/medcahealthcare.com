<?php

namespace App\Livewire\Marketing;

use App\Models\MarketingCampaign;
use App\Models\MarketingCommunicationSnapshot;
use App\Models\MarketingEmailTracker;
use App\Models\MarketingSetting;
use App\Services\Marketing\Ga4DataApiService;
use App\Services\Marketing\GoogleAdsReportService;
use App\Services\Marketing\MarketingInsightsService;
use App\Services\Marketing\MetaAdsReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    use AuthorizesRequests;

    public string $tab = 'overview';

    public ?string $flash = null;

    public string $ga4_measurement_id = '';

    public string $ga4_property_id = '';

    public string $google_ads_aw_id = '';

    public string $meta_pixel_id = '';

    public string $campaign_name = '';

    public string $campaign_platform = 'google_ads';

    public ?string $campaign_budget = null;

    public string $campaign_status = 'active';

    public string $snapshot_channel = 'sms';

    public ?string $snapshot_period_start = null;

    public ?string $snapshot_period_end = null;

    public string $sms_sent = '';

    public string $sms_delivered = '';

    public string $email_sent = '';

    public string $email_open_rate = '';

    public string $email_ctr = '';

    public string $gmb_views = '';

    public string $gmb_calls = '';

    public string $gmb_directions = '';

    public string $gmb_reviews = '';

    public string $whatsapp_conversations = '';

    public string $email_tracker_label = '';

    /** @var array<string, mixed> */
    public array $ga4Bundle = [];

    /** @var array<string, mixed> */
    public array $googleAds = [];

    /** @var array<string, mixed> */
    public array $metaAds = [];

    /** @var list<array{type: string, message: string}> */
    public array $insights = [];

    public ?string $geminiNarrative = null;

    public function mount(): void
    {
        $this->authorize('view', MarketingSetting::current());

        $s = MarketingSetting::current();
        $this->ga4_measurement_id = (string) ($s->ga4_measurement_id ?? '');
        $this->ga4_property_id = (string) ($s->ga4_property_id ?? '');
        $this->google_ads_aw_id = (string) ($s->google_ads_aw_id ?? '');
        $this->meta_pixel_id = (string) ($s->meta_pixel_id ?? '');

        $this->loadReports();
    }

    public function refreshData(): void
    {
        Ga4DataApiService::forgetCache(MarketingSetting::current());
        $this->loadReports();
        $this->flash = __('Reports refreshed.');
    }

    public function saveIntegrations(): void
    {
        $this->authorize('update', MarketingSetting::current());

        $this->validate([
            'ga4_measurement_id' => ['nullable', 'string', 'max:64'],
            'ga4_property_id' => ['nullable', 'string', 'max:32'],
            'google_ads_aw_id' => ['nullable', 'string', 'max:64'],
            'meta_pixel_id' => ['nullable', 'string', 'max:64'],
        ]);

        $s = MarketingSetting::current();
        $s->fill([
            'ga4_measurement_id' => $this->ga4_measurement_id ?: null,
            'ga4_property_id' => $this->ga4_property_id ?: null,
            'google_ads_aw_id' => $this->google_ads_aw_id ?: null,
            'meta_pixel_id' => $this->meta_pixel_id ?: null,
        ]);
        $s->save();

        Ga4DataApiService::forgetCache($s);

        $this->flash = __('Tracking identifiers saved.');
    }

    public function saveCampaign(): void
    {
        $this->authorize('create', MarketingCampaign::class);

        $this->validate([
            'campaign_name' => ['required', 'string', 'max:255'],
            'campaign_platform' => ['required', 'string', 'max:32'],
            'campaign_budget' => ['nullable', 'numeric', 'min:0'],
            'campaign_status' => ['required', 'string', 'max:24'],
        ]);

        MarketingCampaign::query()->create([
            'name' => $this->campaign_name,
            'platform' => $this->campaign_platform,
            'budget' => $this->campaign_budget !== null && $this->campaign_budget !== '' ? $this->campaign_budget : null,
            'status' => $this->campaign_status,
        ]);

        $this->reset('campaign_name', 'campaign_budget');
        $this->campaign_platform = 'google_ads';
        $this->campaign_status = 'active';

        $this->flash = __('Campaign recorded.');
    }

    public function deleteCampaign(int $id): void
    {
        $c = MarketingCampaign::query()->findOrFail($id);
        $this->authorize('delete', $c);
        $c->delete();
        $this->flash = __('Campaign removed.');
    }

    public function saveCommunicationSnapshot(): void
    {
        $this->authorize('update', MarketingSetting::current());

        $this->validate([
            'snapshot_channel' => ['required', 'in:sms,email,gmb,whatsapp'],
            'snapshot_period_start' => ['nullable', 'date'],
            'snapshot_period_end' => ['nullable', 'date', 'after_or_equal:snapshot_period_start'],
        ]);

        $metrics = match ($this->snapshot_channel) {
            'sms' => [
                'sent' => (int) $this->sms_sent ?: 0,
                'delivered' => (int) $this->sms_delivered ?: 0,
            ],
            'email' => [
                'sent' => (int) $this->email_sent ?: 0,
                'open_rate' => (float) $this->email_open_rate ?: 0,
                'ctr' => (float) $this->email_ctr ?: 0,
            ],
            'gmb' => [
                'views' => (int) $this->gmb_views ?: 0,
                'calls' => (int) $this->gmb_calls ?: 0,
                'directions' => (int) $this->gmb_directions ?: 0,
                'reviews' => (int) $this->gmb_reviews ?: 0,
            ],
            'whatsapp' => [
                'conversations' => (int) $this->whatsapp_conversations ?: 0,
            ],
            default => [],
        };

        MarketingCommunicationSnapshot::query()->create([
            'channel' => $this->snapshot_channel,
            'period_start' => $this->snapshot_period_start,
            'period_end' => $this->snapshot_period_end,
            'metrics' => $metrics,
        ]);

        $this->flash = __('Communication metrics saved.');
    }

    public function createEmailTracker(): void
    {
        $this->authorize('update', MarketingSetting::current());

        $this->validate([
            'email_tracker_label' => ['nullable', 'string', 'max:255'],
        ]);

        MarketingEmailTracker::createWithToken($this->email_tracker_label ?: null);
        $this->reset('email_tracker_label');
        $this->flash = __('Open-tracking pixel created — copy the URL from the list.');
    }

    public function render(): View
    {
        $campaigns = MarketingCampaign::query()->latest()->limit(50)->get();
        $snapshots = MarketingCommunicationSnapshot::query()->latest()->limit(40)->get();
        $emailTrackers = MarketingEmailTracker::query()->latest()->limit(20)->get();

        $whatsappClicks = 0;
        foreach ($this->ga4Bundle['events'] ?? [] as $ev) {
            if (($ev['name'] ?? '') === 'whatsapp_click') {
                $whatsappClicks += (int) ($ev['count'] ?? 0);
            }
        }

        return view('livewire.marketing.dashboard', [
            'campaigns' => $campaigns,
            'snapshots' => $snapshots,
            'emailTrackers' => $emailTrackers,
            'whatsappClicks' => $whatsappClicks,
            'ga4DashboardUrl' => config('marketing.ga4_dashboard_url'),
        ]);
    }

    protected function loadReports(): void
    {
        $settings = MarketingSetting::current();
        $this->ga4Bundle = app(Ga4DataApiService::class)->fetchReportBundle($settings);
        $this->googleAds = app(GoogleAdsReportService::class)->fetchSummary();
        $this->metaAds = app(MetaAdsReportService::class)->fetchSummary();

        $snapshots = MarketingCommunicationSnapshot::query()->latest()->limit(40)->get();
        $snapshotRows = $snapshots->map(fn ($s) => [
            'channel' => $s->channel,
            'metrics' => $s->metrics ?? [],
        ])->all();

        $insightService = app(MarketingInsightsService::class);
        $this->insights = $insightService->ruleBasedInsights(
            $this->ga4Bundle,
            $this->googleAds,
            $this->metaAds,
            $snapshotRows
        );

        $this->geminiNarrative = $insightService->geminiNarrative($this->insights);
    }
}
