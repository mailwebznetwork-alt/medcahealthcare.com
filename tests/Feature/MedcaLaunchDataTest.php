<?php

use App\Enums\PublishStatus;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Service;
use Database\Seeders\MedcaLaunchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MedcaLaunchSeeder::class);
});

it('seeds six published medca services with seo and pincodes', function () {
    $codes = [
        'homenursing-services',
        'elder-care',
        'caregivers',
        'doctor-home-visit',
        'physiotherapy-at-home',
        'icu-care-at-home',
    ];

    foreach ($codes as $code) {
        $service = Service::query()->where('service_code', $code)->first();
        expect($service)->not->toBeNull()
            ->and($service->publish_status)->toBe(PublishStatus::Published)
            ->and($service->is_active)->toBeTrue()
            ->and($service->featured_image)->not->toBeEmpty()
            ->and($service->gallery)->toBeArray()->not->toBeEmpty()
            ->and($service->seo)->not->toBeNull()
            ->and($service->pincodes()->count())->toBeGreaterThan(0)
            ->and($service->detail_page_id)->not->toBeNull();
    }

    expect(Service::query()->publicListing()->count())->toBe(6);
});

it('composes home with near-you block token not layout injection', function () {
    $home = Page::query()->where('slug', 'home')->firstOrFail();

    expect($home->content)->toContain('{{block:near-you-home}}');
});

it('renders marketing pages and service detail publicly', function () {
    $this->get('/')->assertSuccessful();

    foreach (['about-us', 'services', 'locations', 'contact', 'careers'] as $slug) {
        $this->get('/'.$slug)->assertSuccessful();
    }

    $this->get(route('public.services.show', 'homenursing-services'))
        ->assertSuccessful()
        ->assertSee('Home Nursing', false);
});

it('parses contact page blocks without errors bag crash', function () {
    $page = Page::query()->where('slug', 'contact')->first();
    expect($page)->not->toBeNull();

    $html = \App\Services\ContentParser::parse($page->content);
    expect($html)->toContain('lead-name')
        ->and($html)->toContain('Submit request');
});

it('captures leads from contact page flow', function () {
    $this->from('/contact')
        ->post(route('public.leads.store'), [
            'name' => 'Launch QA User',
            'phone' => '9884499901',
            'service' => 'Home Nursing',
            'message' => 'Need nursing visit in Arekere',
            'submission_context' => 'contact_form',
        ])
        ->assertRedirect()
        ->assertSessionHas('lead_status');

    expect(Lead::query()->where('phone', '9884499901')->exists())->toBeTrue();
});

it('exposes robots and sitemap with published services', function () {
    $this->get('/robots.txt')->assertSuccessful();
    $this->get('/sitemap.xml')->assertSuccessful();
    $this->get('/sitemap-services.xml')
        ->assertSuccessful()
        ->assertSee('homenursing-services', false)
        ->assertSee('elder-care', false);
});

it('includes all services on services page carousel tokens', function () {
    $page = Page::query()->where('slug', 'services')->first();
    expect($page)->not->toBeNull()
        ->and($page->content)->toContain('services-block-carousel');

    $block = \App\Models\Block::query()->where('block_slug', 'services-block-carousel')->first();
    expect($block?->code)->toContain('homenursing-services')
        ->and($block?->code)->toContain('icu-care-at-home');
});
