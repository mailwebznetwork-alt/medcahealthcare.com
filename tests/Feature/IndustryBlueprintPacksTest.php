<?php

use App\Models\Page;
use App\Models\User;
use App\Services\Blocks\BlockTemplateSyncService;
use App\Services\Deployment\BlueprintPageGenerator;
use App\Services\Deployment\BlueprintRegistry;
use Database\Seeders\ThemePresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ThemePresetSeeder::class);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_05_31_120000_create_deployment_engine_tables.php']);
    app(BlockTemplateSyncService::class)->sync(backup: false);
});

it('registers industry blueprint packs', function () {
    $registry = app(BlueprintRegistry::class);

    expect($registry->find('home_healthcare'))->not->toBeNull()
        ->and($registry->find('care_home'))->not->toBeNull()
        ->and($registry->find('real_estate'))->not->toBeNull()
        ->and($registry->find('cosmetics_clinic'))->not->toBeNull()
        ->and($registry->forIndustry('healthcare'))->not->toBeEmpty()
        ->and($registry->forIndustry('real_estate'))->not->toBeEmpty();
});

it('generates full healthcare pack with service lines and shared elements', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $result = app(BlueprintPageGenerator::class)->generate(
        'home_healthcare',
        'healthcare_professional',
        'clinical_blue',
        'contained',
        $user,
    );

    expect(count($result['pages']))->toBeGreaterThanOrEqual(12);

    $home = Page::query()->where('slug', 'home')->first();
    expect($home->content)->toContain('{{block:hero-healthcare}}')
        ->and($home->content)->toContain('{{block:trust-bar-icons}}');

    $nursing = Page::query()->where('slug', 'service-nursing')->first();
    expect($nursing)->not->toBeNull()
        ->and($nursing->content)->toContain('{{block:cta-banner}}');

    $faq = Page::query()->where('slug', 'faq')->first();
    expect($faq->content)->toContain('{{block:faq-accordion}}');
});

it('generates care home pack with admissions and reviews', function () {
    $user = User::factory()->create(['role' => 'admin']);

    app(BlueprintPageGenerator::class)->generate(
        'care_home',
        'healthcare_premium',
        'premium_gold',
        'contained',
        $user,
    );

    expect(Page::query()->where('slug', 'admissions')->exists())->toBeTrue()
        ->and(Page::query()->where('slug', 'facilities')->exists())->toBeTrue()
        ->and(Page::query()->where('slug', 'reviews')->exists())->toBeTrue();
});

it('generates real estate and cosmetics packs with landing pages', function () {
    $user = User::factory()->create(['role' => 'admin']);

    app(BlueprintPageGenerator::class)->generate('real_estate', 'modern_purple', 'modern_purple', 'wide', $user);
    app(BlueprintPageGenerator::class)->generate('cosmetics_clinic', 'luxury_black', 'luxury_black', 'contained', $user);

    expect(Page::query()->where('slug', 'listings')->exists())->toBeTrue()
        ->and(Page::query()->where('slug', 'property-enquiry')->exists())->toBeTrue()
        ->and(Page::query()->where('slug', 'before-after')->exists())->toBeTrue()
        ->and(Page::query()->where('slug', 'free-consultation')->exists())->toBeTrue();
});

it('stores pack metadata on blueprint definitions', function () {
    $healthcare = app(BlueprintRegistry::class)->find('home_healthcare');

    expect($healthcare['pack_meta']['services'] ?? [])->toContain('Home Care')
        ->and($healthcare['pack_meta']['cta_strategy'] ?? '')->not->toBe('');
});
