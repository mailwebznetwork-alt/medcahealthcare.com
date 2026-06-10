<?php

use App\Models\Page;
use App\Models\ServiceCategory;
use App\Models\SiteNavigationItem;
use App\Models\User;
use App\Services\SiteNavigationTreeService;

it('shows an assigned live page label on the public home header', function () {
    $page = Page::factory()->create([
        'title' => 'Unique Nav Title XYZ',
        'slug' => 'unique-nav-xyz',
        'is_active' => true,
    ]);

    SiteNavigationItem::query()->create([
        'zone' => SiteNavigationItem::ZONE_HEADER,
        'page_id' => $page->id,
        'sort_order' => 0,
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Unique Nav Title XYZ', false);
});

it('allows editors to open the navigation workspace', function () {
    $user = User::factory()->create(['role' => 'editor']);

    $this->actingAs($user)
        ->get(route('site-architect.navigation.index'))
        ->assertSuccessful();
});

it('shows a custom navigation label on the public header when set', function () {
    $page = Page::factory()->create([
        'title' => 'Internal Workspace Title',
        'slug' => 'custom-nav-label-slug',
        'is_active' => true,
    ]);

    SiteNavigationItem::query()->create([
        'zone' => SiteNavigationItem::ZONE_HEADER,
        'page_id' => $page->id,
        'sort_order' => 0,
        'custom_label' => 'Public Menu Label',
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Public Menu Label', false);
});

it('renders nested header menus on the public site', function () {
    $category = ServiceCategory::factory()->create([
        'name' => 'Medical Lab Nav Unique',
        'code' => 'med-lab-nav-unique',
        'is_active' => true,
    ]);

    app(SiteNavigationTreeService::class)->syncZone(SiteNavigationItem::ZONE_HEADER, [
        [
            'item_type' => SiteNavigationItem::TYPE_GROUP,
            'title' => 'Services Menu Unique',
            'children' => [
                [
                    'item_type' => SiteNavigationItem::TYPE_CATEGORY,
                    'service_category_id' => $category->id,
                    'children' => [],
                ],
            ],
        ],
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Services Menu Unique', false)
        ->assertSee('Medical Lab Nav Unique', false);
});

it('persists and resolves unlimited nested menu depth', function () {
    $category = ServiceCategory::factory()->create([
        'name' => 'Medical Lab Nav',
        'code' => 'med-lab-nav',
        'is_active' => true,
    ]);

    app(SiteNavigationTreeService::class)->syncZone(SiteNavigationItem::ZONE_HEADER, [
        [
            'item_type' => SiteNavigationItem::TYPE_GROUP,
            'title' => 'Services',
            'children' => [
                [
                    'item_type' => SiteNavigationItem::TYPE_CATEGORY,
                    'service_category_id' => $category->id,
                    'children' => [
                        [
                            'item_type' => SiteNavigationItem::TYPE_GROUP,
                            'title' => 'Blood Tests',
                            'children' => [
                                [
                                    'item_type' => SiteNavigationItem::TYPE_GROUP,
                                    'title' => 'Diabetic Tests',
                                    'children' => [
                                        [
                                            'item_type' => SiteNavigationItem::TYPE_GROUP,
                                            'title' => 'HbA1c',
                                            'children' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $nav = app(\App\Services\SiteNavigationResolver::class)->headerNav();

    expect($nav)->toHaveCount(1)
        ->and($nav[0]['label'])->toBe('Services')
        ->and($nav[0]['children'][0]['label'])->toBe('Medical Lab Nav')
        ->and($nav[0]['children'][0]['children'])->not->toBeEmpty()
        ->and($nav[0]['children'][0]['children'][0]['label'])->toBe('Blood Tests')
        ->and($nav[0]['children'][0]['children'][0]['children'][0]['label'])->toBe('Diabetic Tests')
        ->and($nav[0]['children'][0]['children'][0]['children'][0]['children'][0]['label'])->toBe('HbA1c');
});

it('loads the navigation workspace without exhausting memory', function () {
    Page::factory()->count(5)->create(['is_active' => true]);

    $user = User::factory()->create(['role' => 'editor']);

    $this->actingAs($user)
        ->get(route('site-architect.navigation.index'))
        ->assertSuccessful()
        ->assertSee('Navigation menus', false)
        ->assertSee('Add menu item', false);
});
