<?php

use App\Enums\ServiceVisibility;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\MarketingAttributionSession;
use App\Models\MarketingClickEvent;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Marketing\Attribution\AttributionReportingService;
use App\Services\Public\PublicRouteAttributionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves service location paths using public route rules', function () {
    $service = Service::factory()->create([
        'service_code' => 'nursing-care',
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $pin = PinCode::factory()->create(['pincode' => '560076', 'is_active' => true]);
    $page = Page::factory()->create(['is_active' => true]);

    $mapping = ServiceLocationPage::query()->create([
        'service_id' => $service->id,
        'pincode_id' => $pin->id,
        'page_id' => $page->id,
        'slug' => 'nursing-care-bangalore-560076',
        'location_slug' => 'bangalore-560076',
        'city_slug' => 'bangalore',
        'is_indexable' => true,
    ]);

    $context = app(PublicRouteAttributionResolver::class)
        ->resolveFromPath('/services/nursing-care/bangalore-560076');

    expect($context->serviceId)->toBe($service->id)
        ->and($context->pinCodeId)->toBe($pin->id)
        ->and($context->serviceLocationPageId)->toBe($mapping->id)
        ->and($context->pageId)->toBe($page->id);
});

it('persists marketing attribution sessions on page views', function () {
    $this->get('/?utm_source=google&utm_medium=cpc&utm_campaign=test')->assertSuccessful();

    expect(MarketingAttributionSession::query()->count())->toBe(1);

    $session = MarketingAttributionSession::query()->first();
    expect($session->utm_source)->toBe('google')
        ->and($session->utm_campaign)->toBe('test')
        ->and($session->landing_page_path)->toBe('/');
});

it('enriches marketing click events with attribution foreign keys', function () {
    $service = Service::factory()->create([
        'service_code' => 'caregivers',
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $this->get('/services/caregivers?utm_source=meta')->assertSuccessful();

    $this->postJson(route('marketing.track'), [
        'event_type' => 'phone_click',
        'page_path' => '/services/caregivers',
        'session_fingerprint' => session()->getId(),
    ])->assertOk()->assertJson(['recorded' => true]);

    $event = MarketingClickEvent::query()->first();
    expect($event)->not->toBeNull()
        ->and($event->service_id)->toBe($service->id)
        ->and($event->marketing_attribution_session_id)->not->toBeNull();
});

it('stitches phone clicks to leads with service attribution', function () {
    config(['security.lead_api_key' => 'test-key']);

    $service = Service::factory()->create([
        'service_code' => 'physio',
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $this->get('/services/physio?utm_source=google')->assertSuccessful();

    $this->postJson(route('marketing.track'), [
        'event_type' => 'phone_click',
        'page_path' => '/services/physio',
        'session_fingerprint' => session()->getId(),
    ])->assertJson(['recorded' => true]);

    $this->withHeader('X-API-KEY', 'test-key')
        ->postJson('/api/leads', [
            'name' => 'Stitch Test',
            'phone' => '9123456789',
            'service' => 'Physio',
            'landing_page' => url('/services/physio'),
        ])
        ->assertCreated();

    $lead = Lead::query()->where('phone', '9123456789')->first();
    $click = MarketingClickEvent::query()->first();

    expect($lead->service_id)->toBe($service->id)
        ->and($click->lead_id)->toBe($lead->id)
        ->and($click->service_id)->toBe($service->id);
});

it('reports attributed leads by service', function () {
    $service = Service::factory()->create(['title' => 'Nursing']);

    Lead::query()->create([
        'name' => 'A',
        'phone' => '9000000001',
        'service' => 'Nursing',
        'source' => LeadSource::Direct,
        'status' => LeadStatus::New,
        'service_id' => $service->id,
        'utm_campaign' => 'spring',
        'landing_page' => '/services/nursing',
    ]);

    $summary = app(AttributionReportingService::class)->clickToLeadSummary();
    $topServices = app(AttributionReportingService::class)->topServices();

    expect($summary['leads'])->toBe(1)
        ->and($topServices->first()->label)->toBe('Nursing');
});
