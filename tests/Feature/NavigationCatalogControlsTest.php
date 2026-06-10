<?php

use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SiteNavigationItem;
use App\Models\User;
use App\Services\SiteNavigationTreeService;
use Livewire\Livewire;

it('shows submenu and remove controls on auto-synced catalog nodes', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $servicesPage = Page::factory()->create(['slug' => 'services', 'title' => 'Services', 'is_active' => true]);

    SiteNavigationItem::query()->create([
        'zone' => SiteNavigationItem::ZONE_HEADER,
        'item_type' => SiteNavigationItem::TYPE_PAGE,
        'page_id' => $servicesPage->id,
        'sort_order' => 0,
    ]);

    $category = ServiceCategory::factory()->create([
        'name' => 'Controls Nav Category',
        'code' => 'controls-nav-cat',
        'is_active' => true,
    ]);

    $service = Service::factory()->create([
        'title' => 'Controls Nav Service',
        'service_code' => 'controls-nav-svc',
        'is_active' => true,
    ]);

    $category->services()->attach($service->id);

    Livewire::actingAs($user)
        ->test(\App\Livewire\SiteArchitect\NavigationSystem::class)
        ->assertSee('Controls Nav Category')
        ->assertSee('Auto from catalog')
        ->assertSeeHtml('data-lucide="plus"')
        ->assertSeeHtml('data-lucide="trash-2"');
});

it('excludes auto-synced catalog items when removed from navigation', function () {
    $servicesPage = Page::factory()->create(['slug' => 'services', 'title' => 'Services', 'is_active' => true]);

    SiteNavigationItem::query()->create([
        'zone' => SiteNavigationItem::ZONE_HEADER,
        'item_type' => SiteNavigationItem::TYPE_PAGE,
        'page_id' => $servicesPage->id,
        'sort_order' => 0,
    ]);

    $category = ServiceCategory::factory()->create([
        'name' => 'Hidden Nav Category',
        'code' => 'hidden-nav-cat',
        'is_active' => true,
    ]);

    $treeService = app(SiteNavigationTreeService::class);
    $tree = $treeService->serializeZone(SiteNavigationItem::ZONE_HEADER);
    $servicesIndex = collect($tree)->search(fn (array $node): bool => (int) ($node['page_id'] ?? 0) === $servicesPage->id);

    expect($servicesIndex)->not->toBeFalse();

    Livewire::actingAs(User::factory()->create(['role' => 'admin']))
        ->test(\App\Livewire\SiteArchitect\NavigationSystem::class)
        ->call('removeMenuItem', 'header', [(int) $servicesIndex, 0]);

    $serialized = app(SiteNavigationTreeService::class)->serializeZone(SiteNavigationItem::ZONE_HEADER);
    $servicesNode = collect($serialized)->first(fn (array $node): bool => (int) ($node['page_id'] ?? 0) === $servicesPage->id);

    expect(collect($servicesNode['children'] ?? [])->pluck('label'))->not->toContain('Hidden Nav Category');
});
