<?php

use App\Models\BusinessProfile;
use App\Models\PinCode;
use App\Models\PinCodeHospital;
use App\Models\PinCodeLandmark;
use App\Models\PinCodeNearbyArea;
use App\Models\SeoEntity;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Seo\ConversationalAeoFaqBuilder;
use App\Services\Seo\UnifiedJsonLdGraphBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds dynamic medical business from business profile not hardcoded city', function () {
    $profile = BusinessProfile::query()->create([
        'name' => 'Medca Healthcare Pvt Ltd',
        'phone_e164' => '+918884999002',
        'street_address' => 'Sample Street',
        'city' => 'Bengaluru',
        'region' => 'Karnataka',
        'postal_code' => '560099',
        'country_code' => 'IN',
        'website' => config('app.url'),
    ]);

    SeoEntity::query()->create([
        'business_profile_id' => $profile->id,
        'organization_name' => 'Medca Health Care',
        'meta_description' => 'Doctor-led home healthcare services.',
        'same_as' => ['https://example.com/gbp'],
    ]);

    $service = Service::factory()->create([
        'title' => 'Home Nursing',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $graph = app(UnifiedJsonLdGraphBuilder::class)->buildServiceGraph($service->fresh(['seo', 'faqs', 'pincodes']));

    $business = collect($graph['@graph'])->first(fn ($n) => in_array('MedicalBusiness', (array) ($n['@type'] ?? []), true));

    expect($business)->not->toBeNull()
        ->and($business['telephone'])->toBe('+918884999002')
        ->and($business['address']['postalCode'] ?? null)->toBe('560099')
        ->and($business['sameAs'])->toContain('https://example.com/gbp');
});

it('generates conversational location faqs from pincode hospitals', function () {
    $service = Service::factory()->create(['title' => 'Physiotherapy at Home']);
    $pin = PinCode::factory()->create([
        'area_name' => 'Test Area',
        'city' => 'Metro City',
        'pincode' => '600001',
        'coverage_text' => 'Coverage across Test Area blocks.',
    ]);
    PinCodeHospital::create([
        'pincode_id' => $pin->id,
        'name' => 'City General Hospital',
        'specialty' => 'Multispecialty',
        'sort_order' => 0,
    ]);

    $faqs = app(ConversationalAeoFaqBuilder::class)->forLocation($service, $pin->fresh(['hospitals']));

    expect($faqs)->not->toBeEmpty();
    $questions = collect($faqs)->pluck('name')->implode(' ');
    expect($questions)
        ->toContain('Physiotherapy at Home')
        ->toContain('Test Area')
        ->toContain('600001')
        ->toContain('City General Hospital');
});

it('location graph includes hospitals landmarks and geographic area entities', function () {
    $service = Service::factory()->create([
        'title' => 'Elder Care',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    $pin = PinCode::factory()->create([
        'area_name' => 'District One',
        'city' => 'Metro City',
        'pincode' => '600002',
        'coverage_text' => 'Local elder care coverage.',
    ]);
    PinCodeHospital::create(['pincode_id' => $pin->id, 'name' => 'Metro Hospital', 'sort_order' => 0]);
    PinCodeLandmark::create(['pincode_id' => $pin->id, 'name' => 'Central Park', 'sort_order' => 0]);
    PinCodeNearbyArea::create(['pincode_id' => $pin->id, 'area_name' => 'District Two', 'sort_order' => 0]);

    $page = \App\Models\Page::factory()->create(['slug' => 'svc-elder-loc-600002']);
    $mapping = ServiceLocationPage::create([
        'service_id' => $service->id,
        'pincode_id' => $pin->id,
        'page_id' => $page->id,
        'slug' => $page->slug,
        'location_slug' => 'district-one',
        'city_slug' => 'metro-city',
        'is_indexable' => true,
    ]);

    $graph = app(UnifiedJsonLdGraphBuilder::class)->buildLocationGraph(
        $service->fresh(),
        $pin->fresh(['landmarks', 'hospitals', 'nearbyAreas', 'locationFaqs']),
        $mapping
    );

    $types = collect($graph['@graph'])->pluck('@type')->flatten()->unique()->values()->all();

    expect($types)
        ->toContain('Hospital')
        ->toContain('FAQPage')
        ->toContain('BreadcrumbList');

    $ids = collect($graph['@graph'])->pluck('@id')->filter()->values()->all();
    expect($ids)->toContain($mapping->publicUrl().'#geographic-area');
});

it('emits only one json ld graph document per service url', function () {
    $service = Service::factory()->create([
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $graph = app(UnifiedJsonLdGraphBuilder::class)->buildServiceGraph($service->fresh(['seo', 'faqs', 'pincodes']));

    expect($graph)->toHaveKeys(['@context', '@graph'])
        ->and($graph['@context'])->toBe('https://schema.org')
        ->and(count($graph['@graph']))->toBeGreaterThan(3);
});
