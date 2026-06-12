<?php

use App\Models\GlobalContentVariable;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Deployment\GlobalContentVariableRepository;
use App\Support\BlockContent;
use Database\Seeders\GlobalContentVariableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GlobalContentVariableSeeder::class);
});

it('saves brand story fields from appearance settings', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Settings\AppearanceSettings::class)
        ->set('activeTab', 'brand_story')
        ->set('brandStory.mission_statement', 'Test mission from admin.')
        ->set('brandStory.care_model', 'Test care model from admin.')
        ->call('saveBrandStory')
        ->assertHasNoErrors();

    expect(GlobalContentVariable::query()->where('key', 'mission_statement')->value('value'))
        ->toBe('Test mission from admin.')
        ->and(app(GlobalContentVariableRepository::class)->resolved()['care_model'])
        ->toBe('Test care model from admin.');
});

it('resolves block copy from global content when block field is empty', function () {
    app(GlobalContentVariableRepository::class)->sync([
        'mission_statement' => 'Global mission text.',
        'trust_pillars' => "Line one\nLine two",
    ], User::factory()->create(['role' => 'admin']));

    expect(BlockContent::globalOrBlock([], 'body-about', 'mission_body', 'mission_statement'))
        ->toBe('Global mission text.')
        ->and(BlockContent::globalLinesOrBlock([], 'body-about', 'trust_bullets', 'trust_pillars'))
        ->toBe(['Line one', 'Line two']);
});

it('groups global content fields for the editor', function () {
    $grouped = app(GlobalContentVariableRepository::class)->forEditorGrouped();

    expect($grouped)->toHaveKeys(['identity', 'brand_story', 'home', 'contact'])
        ->and($grouped['brand_story']['fields'])->toHaveKey('mission_statement')
        ->and($grouped['home']['fields'])->toHaveKey('home_hero_headline');
});
