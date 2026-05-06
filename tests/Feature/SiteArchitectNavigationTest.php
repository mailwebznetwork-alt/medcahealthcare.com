<?php

use App\Models\Page;
use App\Models\SiteNavigationItem;
use App\Models\User;

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
