<?php

use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SiteNavigationItem;
use App\Models\SubService;
use App\Services\SiteNavigationResolver;

it('auto-populates services dropdown from the live catalog', function () {
    $servicesPage = Page::factory()->create(['slug' => 'services', 'title' => 'Services', 'is_active' => true]);

    SiteNavigationItem::query()->create([
        'zone' => SiteNavigationItem::ZONE_HEADER,
        'item_type' => SiteNavigationItem::TYPE_PAGE,
        'page_id' => $servicesPage->id,
        'sort_order' => 2,
        'custom_label' => 'Services',
    ]);

    $category = ServiceCategory::factory()->create([
        'name' => 'Auto Nav Category',
        'code' => 'auto-nav-cat',
        'is_active' => true,
    ]);

    $service = Service::factory()->create([
        'title' => 'Auto Nav Service',
        'service_code' => 'auto-nav-svc',
        'is_active' => true,
    ]);

    $category->services()->attach($service->id);

    SubService::query()->create([
        'service_id' => $service->id,
        'title' => 'Auto Nav Sub Service',
        'sub_service_code' => 'auto-nav-sub',
        'is_active' => true,
        'publish_status' => \App\Enums\PublishStatus::Published,
        'visibility' => \App\Enums\ServiceVisibility::Public,
    ]);

    $nav = app(SiteNavigationResolver::class)->headerNav();
    $servicesNode = collect($nav)->first(fn (array $node): bool => ($node['label'] ?? '') === 'Services');

    expect($servicesNode)->not->toBeNull()
        ->and($servicesNode['children'])->not->toBeEmpty()
        ->and(collect($servicesNode['children'])->pluck('label'))->toContain('Auto Nav Category');

    $categoryNode = collect($servicesNode['children'])->firstWhere('label', 'Auto Nav Category');
    expect(collect($categoryNode['children'])->pluck('label'))->toContain('Auto Nav Service');

    $serviceNode = collect($categoryNode['children'])->firstWhere('label', 'Auto Nav Service');
    expect(collect($serviceNode['children'])->pluck('label'))->toContain('Auto Nav Sub Service');
});

it('renders services header link to the services page when the dropdown has catalog children', function () {
    $servicesPage = Page::factory()->create(['slug' => 'services', 'title' => 'Services', 'is_active' => true]);

    SiteNavigationItem::query()->create([
        'zone' => SiteNavigationItem::ZONE_HEADER,
        'item_type' => SiteNavigationItem::TYPE_PAGE,
        'page_id' => $servicesPage->id,
        'sort_order' => 0,
        'custom_label' => 'Services',
    ]);

    ServiceCategory::factory()->create([
        'name' => 'Linked Category',
        'code' => 'linked-cat',
        'is_active' => true,
    ]);

    $html = view('components.public.nav-item', [
        'item' => collect(app(SiteNavigationResolver::class)->headerNav())->firstWhere('label', 'Services'),
        'navLinkBase' => 'medca-primary-nav-link',
        'navLinkDefault' => 'text-medca-primary',
        'navLinkActive' => 'text-active',
    ])->render();

    expect($html)
        ->toContain('href="'.url('/services').'"', false)
        ->not->toContain('<button', false);
});

it('reflects catalog removals in the services dropdown immediately', function () {
    $servicesPage = Page::factory()->create(['slug' => 'services', 'title' => 'Services', 'is_active' => true]);

    SiteNavigationItem::query()->create([
        'zone' => SiteNavigationItem::ZONE_HEADER,
        'item_type' => SiteNavigationItem::TYPE_PAGE,
        'page_id' => $servicesPage->id,
        'sort_order' => 0,
    ]);

    $category = ServiceCategory::factory()->create([
        'name' => 'Temporary Category',
        'code' => 'temp-cat',
        'is_active' => true,
    ]);

    $resolver = app(SiteNavigationResolver::class);

    expect(collect($resolver->headerNav())->flatMap(fn ($n) => $n['children'] ?? [])->pluck('label'))
        ->toContain('Temporary Category');

    $category->delete();

    expect(collect($resolver->headerNav())->flatMap(fn ($n) => $n['children'] ?? [])->pluck('label'))
        ->not->toContain('Temporary Category');
});
