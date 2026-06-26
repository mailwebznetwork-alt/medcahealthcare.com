<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Services\Operations\CategoryPageProvisioner;
use App\Services\Operations\SubServicePageProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders why medca section when catalog text is present', function () {
    $html = view('components.public.catalog-why-medca', [
        'text' => 'Verified caregivers and 24/7 support.',
    ])->render();

    expect($html)
        ->toContain('Why choose Medca')
        ->toContain('Verified caregivers and 24/7 support.');
});

it('renders trust panel from catalog trust signals', function () {
    $service = Service::factory()->create([
        'trust_signals' => [
            'google_rating' => 4.8,
            'review_count' => 120,
            'years_experience' => '15+ years experience',
        ],
    ]);

    $html = view('components.public.catalog-trust-panel', [
        'entity' => $service,
    ])->render();

    expect($html)
        ->toContain('4.8')
        ->toContain('120 reviews')
        ->toContain('15+ years experience');
});

it('renders why medca inside service detail body', function () {
    $service = Service::factory()->create([
        'why_medca' => 'Medca nurses are ICU-trained and background verified.',
        'short_summary' => 'Professional home nursing.',
    ]);

    $html = view('components.public.service-detail-body', [
        'service' => $service,
        'showReviewForm' => false,
    ])->render();

    expect($html)->toContain('Medca nurses are ICU-trained and background verified.');
});

it('injects sub-service detail body block into legacy hero-only pages', function () {
    $service = Service::factory()->create([
        'service_code' => 'legacy-parent',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'legacy-sub',
        'title' => 'Legacy Sub Service',
        'why_medca' => 'Same-day sample collection.',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $page = app(SubServicePageProvisioner::class)->syncFromSubService($sub->fresh());
    $page->forceFill(['content' => '{{block:sub-service-detail-hero}}'])->saveQuietly();

    $page = app(SubServicePageProvisioner::class)->syncFromSubService($sub->fresh());

    expect($page->content)
        ->toContain('sub-service-detail-hero')
        ->toContain('sub-service-detail-body');
});

it('injects category detail body block into legacy category pages', function () {
    $pin = \App\Models\PinCode::factory()->create(['is_active' => true]);

    $category = ServiceCategory::query()->create([
        'name' => 'Legacy Category',
        'code' => 'legacy-cat',
        'why_medca' => 'Doctor-led care plans.',
        'is_active' => true,
        'publish_status' => 'published',
        'visibility' => 'public',
    ]);
    $category->pincodes()->attach($pin->id);

    $page = app(CategoryPageProvisioner::class)->syncFromCategory($category->fresh());
    $page->forceFill([
        'content' => "{{block:category-discovery-hero}}\n{{block:category-services-list}}",
    ])->saveQuietly();

    $page = app(CategoryPageProvisioner::class)->syncFromCategory($category->fresh());

    expect($page->content)
        ->toContain('category-discovery-hero')
        ->toContain('category-detail-body')
        ->toContain('category-services-list');
});

it('renders sub-service public body with benefits and faq sections', function () {
    $service = Service::factory()->create(['service_code' => 'parent-svc']);

    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'public-sub',
        'title' => 'Public Sub',
        'why_medca' => 'Same-day home visits.',
        'key_benefits' => ['Fast turnaround', 'Certified staff'],
        'process_steps' => ['Book online', 'Nurse visits home'],
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $sub->faqs()->create([
        'question' => 'How fast is the visit?',
        'answer' => 'Within 60 minutes in Bangalore.',
        'sort_order' => 0,
    ]);

    $html = view('components.public.sub-service-detail-body', [
        'subService' => $sub->fresh(['faqs', 'service']),
    ])->render();

    expect($html)
        ->toContain('Same-day home visits.')
        ->toContain('Key benefits')
        ->toContain('How fast is the visit?')
        ->toContain('Within 60 minutes in Bangalore.');
});
