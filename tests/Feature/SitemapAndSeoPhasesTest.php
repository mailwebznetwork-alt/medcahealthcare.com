<?php

use App\Jobs\Growth\RegenerateSitemapJob;
use App\Models\Admission;
use App\Models\Page;
use App\Models\RevenueEvent;
use App\Models\Service;
use App\Services\Growth\SeoService;
use App\Services\Growth\SeoSitemapFileGenerator;
use App\Services\Seo\DataDrivenSeoResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('generates paginated sitemap index segments', function () {
    config(['sitemap.paginated_enabled' => true]);

    $index = app(SeoService::class)->generateSitemapIndex();

    expect($index)->toContain('sitemap-static-pages.xml')
        ->and($index)->toContain('sitemap-services.xml')
        ->and($index)->toContain('sitemap-locations-001.xml');
});

it('queues sitemap regeneration on page save', function () {
    Queue::fake();
    config(['sitemap.queue_enabled' => true, 'sitemap.cache_enabled' => true]);

    Page::factory()->create(['title' => 'Test', 'slug' => 'test-page', 'is_active' => true]);

    Queue::assertPushed(RegenerateSitemapJob::class);
});

it('writes cached sitemap files', function () {
    Storage::fake(config('sitemap.cache_disk', 'local'));
    config(['sitemap.cache_enabled' => true, 'sitemap.paginated_enabled' => true]);

    app(SeoSitemapFileGenerator::class)->regenerateAll();

    Storage::disk('local')->assertExists('sitemaps/sitemap.xml');
    Storage::disk('local')->assertExists('sitemaps/sitemap-static-pages.xml');
});

it('resolves data driven seo for generated pages', function () {
    config(['seo_rules.enabled' => true]);

    $service = Service::factory()->create(['title' => 'Home Nursing']);

    $resolved = app(DataDrivenSeoResolver::class)->resolve(
        page: Page::factory()->make(['page_source' => 'generated', 'slug' => 'svc-loc']),
        service: $service,
    );

    expect($resolved)->not->toBeNull()
        ->and($resolved['meta_title'])->toContain('Home Nursing');
});

it('records admissions and revenue with attribution fields', function () {
    $service = Service::factory()->create();

    $admission = Admission::query()->create([
        'patient_name' => 'Test Patient',
        'status' => 'admitted',
        'service_id' => $service->id,
        'admitted_at' => now(),
    ]);

    RevenueEvent::query()->create([
        'admission_id' => $admission->id,
        'service_id' => $service->id,
        'amount' => 15000,
        'currency' => 'INR',
        'recorded_at' => now(),
    ]);

    expect(Admission::query()->count())->toBe(1)
        ->and((float) RevenueEvent::query()->sum('amount'))->toBe(15000.0);
});
