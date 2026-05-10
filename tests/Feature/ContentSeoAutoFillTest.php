<?php

use App\Models\BusinessProfile;
use App\Models\Page;
use App\Models\PageElement;
use App\Models\PageSeo;
use App\Models\SeoAiSignal;
use App\Models\Service;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Config::set('growth.content_seo_auto_fill', true);
    Config::set('growth.content_seo_fill_only_empty', true);
    Config::set('growth.content_seo_gemini', false);

    BusinessProfile::query()->firstOrCreate(
        ['website' => config('app.url')],
        ['name' => 'Site', 'email' => 'x@test.test']
    );
});

it('fills empty page seo fields and syncs growth tables', function (): void {
    $page = Page::factory()->create([
        'title' => 'About Care',
        'slug' => 'about-care',
        'meta_title' => null,
        'meta_description' => null,
        'h1' => null,
        'canonical_url' => null,
        'aeo_question' => null,
        'aeo_answer' => null,
        'content' => '<p>We provide premium healthcare in Bangalore.</p>',
        'schema_json' => null,
    ]);

    $page->refresh();

    expect($page->meta_title)->toBe('About Care');
    expect($page->h1)->toBe('About Care');
    expect($page->meta_description)->not->toBeEmpty();
    expect($page->canonical_url)->toContain('/p/about-care');

    expect(PageSeo::query()->where('page_slug', '/p/about-care')->exists())->toBeTrue();
    expect(PageElement::query()->where('page_slug', '/p/about-care')->count())->toBeGreaterThan(0);

    expect(SeoAiSignal::query()->exists())->toBeTrue();
});

it('fills service seo and syncs page_seo for services slug', function (): void {
    $service = Service::factory()->create([
        'title' => 'Dental Checkup',
        'service_code' => 'dental-checkup',
    ]);

    $service->refresh()->load('seo');

    expect($service->seo)->not->toBeNull();
    expect($service->seo->meta_title)->toContain('Dental');
    expect(PageSeo::query()->where('page_slug', 'services/dental-checkup')->exists())->toBeTrue();
});
