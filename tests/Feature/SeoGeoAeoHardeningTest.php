<?php

use App\Jobs\Operations\RefreshServiceInternalLinksJob;
use App\Models\PinCode;
use App\Models\PinCodeLocationFaq;
use App\Models\Service;
use App\Services\Operations\LocationPageQualityScorer;
use App\Services\Operations\ServiceInternalLinkingEngine;
use App\Services\Seo\UnifiedJsonLdGraphBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('builds a single unified service graph with medical organization', function () {
    $service = Service::factory()->create([
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    $service->faqs()->create(['question' => 'Q1?', 'answer' => 'A1', 'sort_order' => 0]);

    $graph = app(UnifiedJsonLdGraphBuilder::class)->buildServiceGraph($service->fresh(['seo', 'faqs']));

    expect($graph)->toHaveKey('@graph')
        ->and(count($graph['@graph']))->toBeGreaterThanOrEqual(4);

    $types = collect($graph['@graph'])->pluck('@type')->flatten()->unique()->values()->all();
    expect($types)->toContain('MedicalOrganization', 'Service', 'BreadcrumbList');
});

it('dispatches internal link refresh job on service save', function () {
    Bus::fake([RefreshServiceInternalLinksJob::class]);

    Service::factory()->create();

    Bus::assertDispatched(RefreshServiceInternalLinksJob::class);
});

it('filters unpublished services from related links', function () {
    $published = Service::factory()->create([
        'service_code' => 'pub-svc',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    Service::factory()->create([
        'service_code' => 'draft-svc',
        'publish_status' => 'draft',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $links = app(ServiceInternalLinkingEngine::class)->build($published->fresh());

    $codes = collect($links['related_services'])->pluck('code')->all();
    expect($codes)->not->toContain('draft-svc');
});

it('scores location page quality from pincode geo dataset', function () {
    $service = Service::factory()->create([
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
        'short_summary' => 'Doctor-led nursing at home.',
    ]);
    $pin = PinCode::factory()->create([
        'coverage_text' => 'We cover the local area and surrounding blocks.',
        'city' => 'Metro City',
        'area_name' => 'District One',
    ]);
    $service->pincodes()->attach($pin->id, ['is_visible' => true, 'priority' => 1]);
    $pin = $service->pincodes()->where('pin_codes.id', $pin->id)->first();
    PinCodeLocationFaq::create([
        'pincode_id' => $pin->id,
        'question' => 'Do you serve District One?',
        'answer' => 'Yes, same-day visits are available.',
        'sort_order' => 0,
    ]);

    $page = \App\Models\Page::factory()->create(['slug' => 'service-test-loc-560076']);
    $mapping = \App\Models\ServiceLocationPage::create([
        'service_id' => $service->id,
        'pincode_id' => $pin->id,
        'page_id' => $page->id,
        'slug' => $page->slug,
        'location_slug' => 'arekere',
        'city_slug' => 'bangalore',
    ]);

    $scores = app(LocationPageQualityScorer::class)->score($service, $pin, $mapping);

    expect($scores['content_uniqueness'])->toBeGreaterThan(0)
        ->and($scores['geo_readiness'])->toBeGreaterThan(0);
});
