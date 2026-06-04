<?php

use App\Models\Block;
use App\Models\User;
use App\ModuleAccess;
use Livewire\Livewire;

it('opens visual section picker and appends a section without showing slug in the list', function () {
    $user = User::factory()->create([
        'role' => 'editor',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    Block::query()->create([
        'block_slug' => 'cta-banner',
        'block_name' => 'Element — Cta Banner',
        'block_type' => 'CTA',
        'code' => '<p>cta</p>',
        'is_active' => true,
        'is_managed' => true,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\SiteArchitect\Pages::class)
        ->call('startCreate')
        ->call('addSection')
        ->assertSet('sectionPickerOpen', true)
        ->call('appendSection', 'cta-banner')
        ->assertSet('sectionPickerOpen', false)
        ->assertSee('CTA Banner', false);
});

it('hides blocks factory tab for editor role', function () {
    $user = User::factory()->create([
        'role' => 'editor',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($user)
        ->get(route('site-architect.pages.index'))
        ->assertSuccessful()
        ->assertSee(__('Add section'), false)
        ->assertDontSee(__('Blocks Factory'), false);
});
