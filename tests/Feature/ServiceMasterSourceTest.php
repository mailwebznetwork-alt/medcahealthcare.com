<?php

use App\Enums\PageCategory;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Operations\PageCategoryResolver;
use App\Services\Operations\ServiceMasterOrchestrator;
use App\Services\Operations\ServiceOptimizationScorer;

it('scores a service and persists optimization metrics', function () {
    $service = Service::factory()->create([
        'short_summary' => 'Professional home nursing in Bangalore.',
        'description' => str_repeat('Care at home. ', 40),
    ]);

    $service->seo()->updateOrCreate(
        ['service_id' => $service->id],
        [
            'meta_title' => 'Home Nursing Bangalore',
            'meta_description' => str_repeat('Trusted nurses. ', 30),
            'focus_keywords' => ['home nursing'],
            'h1' => 'Home Nursing',
            'ai_context' => 'Clinical home care context.',
        ]
    );

    $service->faqs()->createMany([
        ['question' => 'What is home nursing?', 'answer' => 'Skilled care at home.'],
        ['question' => 'Who is it for?', 'answer' => 'Patients needing support.'],
        ['question' => 'How to book?', 'answer' => 'Call Medca.'],
    ]);

    $result = app(ServiceOptimizationScorer::class)->scoreAndPersist($service->fresh());

    expect($result['seo_score'])->toBeGreaterThan(50)
        ->and($service->fresh()->seo?->seo_score)->toBeGreaterThan(0);
});

it('creates location pages when pincodes are assigned', function () {
    $service = Service::factory()->create([
        'service_code' => 'nursing-test',
        'title' => 'Home Nursing',
    ]);

    $pin = PinCode::factory()->create([
        'pincode' => '560076',
        'area_name' => 'Arekere',
        'city' => 'Bangalore',
        'is_active' => true,
    ]);

    $service->pincodes()->attach($pin->id);

    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes']));

    $mapping = ServiceLocationPage::query()
        ->where('service_id', $service->id)
        ->where('pincode_id', $pin->id)
        ->first();

    expect($mapping)->not->toBeNull()
        ->and($mapping->page)->not->toBeNull()
        ->and($mapping->location_slug)->not->toBeEmpty()
        ->and($mapping->page->page_category)->toBe(PageCategory::Location)
        ->and($mapping->page->title)->toContain('Arekere')
        ->and($mapping->publicUrl())->toContain('/services/nursing-test/');
});

it('removes location pages when pincode is detached', function () {
    $service = Service::factory()->create(['service_code' => 'detach-test']);
    $pin = PinCode::factory()->create(['pincode' => '560001', 'is_active' => true]);
    $service->pincodes()->attach($pin->id);

    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes']));
    expect(ServiceLocationPage::query()->where('service_id', $service->id)->count())->toBe(1);

    $service->pincodes()->sync([]);
    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes']));

    expect(ServiceLocationPage::query()->where('service_id', $service->id)->count())->toBe(0);
});

it('classifies web and service pages', function () {
    $home = Page::factory()->create(['slug' => 'home', 'page_category' => null]);
    expect(app(PageCategoryResolver::class)->resolve($home))->toBe(PageCategory::Web);

    $servicePage = Page::factory()->create(['slug' => 'service-caregivers', 'page_category' => null]);
    expect(app(PageCategoryResolver::class)->resolve($servicePage))->toBe(PageCategory::Service);
});

it('auto-generates service graph schema on master sync', function () {
    $service = Service::factory()->create(['service_code' => 'schema-test']);
    $service->seo()->updateOrCreate(
        ['service_id' => $service->id],
        [
            'meta_title' => 'Schema Test',
            'meta_description' => 'Description for schema.',
            'h1' => 'Schema Test',
        ]
    );

    app(ServiceMasterOrchestrator::class)->sync($service->fresh());

    $service->load('schema');
    expect($service->schema?->schema_type)->toBe('ServiceGraph')
        ->and($service->schema?->schema_json)->toBeArray()
        ->and($service->schema?->schema_json['@graph'] ?? null)->toBeArray();
});
