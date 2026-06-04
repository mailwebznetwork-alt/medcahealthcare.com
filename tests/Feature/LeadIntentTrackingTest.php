<?php

use App\Enums\LeadAttributionBucket;
use App\Enums\LeadIntentChannel;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadIntentEvent;
use App\Models\MarketingClickEvent;
use App\Services\Marketing\LeadIntent\LeadAttributionClassifier;
use App\Services\Marketing\LeadIntent\LeadIntentDashboardService;
use App\Services\Marketing\LeadIntent\LeadIntentRecorder;
use App\Services\Marketing\Tracking\MarketingClickTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Schema::hasTable('lead_intent_events')) {
        $this->markTestSkipped('lead_intent_events migration required');
    }
});

it('classifies google ads from utm', function () {
    $bucket = app(LeadAttributionClassifier::class)->classify('google', 'cpc', 'spring', 'whatsapp_click');

    expect($bucket)->toBe(LeadAttributionBucket::GoogleAds);
});

it('records lead intent when marketing click is stored', function () {
    $request = Request::create('/marketing/track', 'POST', [
        'event_type' => 'whatsapp_click',
        'button_name' => 'Customer Care',
        'phone_number' => '918884999002',
        'page_path' => '/contact',
        'source' => 'google',
        'medium' => 'cpc',
        'campaign' => 'brand',
        'session_fingerprint' => 'test-fp-'.uniqid(),
    ]);

    $result = app(MarketingClickTrackingService::class)->record($request);

    expect($result['recorded'])->toBeTrue();

    $intent = LeadIntentEvent::query()->latest('id')->first();
    expect($intent)->not->toBeNull()
        ->and($intent->intent_type)->toBe('whatsapp_click')
        ->and($intent->channel)->toBe(LeadIntentChannel::WhatsApp)
        ->and($intent->attribution_bucket)->toBe(LeadAttributionBucket::GoogleAds)
        ->and($intent->campaign)->toBe('brand');
});

it('records form intent when a lead is created', function () {
    $lead = Lead::query()->create([
        'name' => 'Test User',
        'phone' => '919999999999',
        'service' => 'Nursing',
        'source' => LeadSource::GoogleAds,
        'status' => LeadStatus::New,
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'nursing-q2',
    ]);

    $intent = LeadIntentEvent::query()->where('lead_id', $lead->id)->first();
    expect($intent)->not->toBeNull()
        ->and($intent->intent_type)->toBe('google_ads_form')
        ->and($intent->channel)->toBe(LeadIntentChannel::Forms);
});

it('builds dashboard totals by channel and bucket', function () {
    LeadIntentEvent::query()->create([
        'intent_type' => 'phone_click',
        'channel' => LeadIntentChannel::Calls,
        'attribution_bucket' => LeadAttributionBucket::Organic,
        'occurred_at' => now(),
    ]);
    LeadIntentEvent::query()->create([
        'intent_type' => 'whatsapp_click',
        'channel' => LeadIntentChannel::WhatsApp,
        'attribution_bucket' => LeadAttributionBucket::GoogleAds,
        'occurred_at' => now(),
    ]);

    $report = app(LeadIntentDashboardService::class)->report(now()->subDay(), now()->addDay());

    expect($report['totals']['calls'])->toBe(1)
        ->and($report['totals']['whatsapp'])->toBe(1)
        ->and($report['totals']['total'])->toBe(2);
});

it('dedupes marketing click intents on backfill', function () {
    $click = MarketingClickEvent::query()->create([
        'event_type' => 'phone_click',
        'page_path' => '/',
        'occurred_at' => now(),
    ]);

    $recorder = app(LeadIntentRecorder::class);
    expect($recorder->recordFromMarketingClick($click))->not->toBeNull();
    expect($recorder->recordFromMarketingClick($click))->toBeNull();
});
