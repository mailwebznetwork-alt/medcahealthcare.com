<?php

use App\Models\BusinessProfile;
use App\Models\SeoEntity;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('redirects legacy aeo tab to seo tab', function () {
    if (! Schema::hasTable('competitors')) {
        $this->markTestSkipped('Competitors table is not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    $this->actingAs($user)
        ->get(route('growth-center.competitors.index', ['tab' => 'aeo']))
        ->assertRedirect(route('growth-center.competitors.index', ['tab' => 'seo']));
});

it('renders pdf section eight engine map on seo tab', function () {
    if (! Schema::hasTable('competitors')) {
        $this->markTestSkipped('Competitors table is not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    $this->actingAs($user)
        ->get(route('growth-center.competitors.index', ['tab' => 'seo']))
        ->assertOk()
        ->assertSee('PDF ഭാഗം 8', false)
        ->assertSee('/growth-center/seo/entity', false);
});

it('redirects legacy geo tab to seo tab', function () {
    if (! Schema::hasTable('competitors')) {
        $this->markTestSkipped('Competitors table is not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    $this->actingAs($user)
        ->get(route('growth-center.competitors.index', ['tab' => 'geo']))
        ->assertRedirect(route('growth-center.competitors.index', ['tab' => 'seo']));
});

it('saves global seo entity with gmb fields and faqs', function () {
    if (! Schema::hasTable('seo_entities') || ! Schema::hasTable('business_profiles')) {
        $this->markTestSkipped('Growth Center SEO tables are not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    $gmbUrl = 'https://www.google.com/maps?cid=123456789';

    $this->actingAs($user)
        ->post(route('growth-center.seo.entity.store'), [
            'organization_name' => 'Medca Test Org',
            'same_as_json' => '[]',
            'google_place_id' => 'ChIJTestPlaceId000',
            'google_business_profile_url' => $gmbUrl,
            'has_map_url' => 'https://maps.google.com/?q=Test',
            'entity_faqs_json' => json_encode([
                ['question' => 'എന്താണ് സേവന സമയം?', 'answer' => 'പ്രഭാതം 9 മുതൽ വൈകുന്നേരം 6 വരെ.'],
            ], JSON_THROW_ON_ERROR),
        ])
        ->assertRedirect(route('growth-center.competitors.index', ['tab' => 'seo']));

    $entity = SeoEntity::query()->where('organization_name', 'Medca Test Org')->first();
    expect($entity)->not->toBeNull();
    expect($entity->google_place_id)->toBe('ChIJTestPlaceId000');
    expect($entity->google_business_profile_url)->toBe($gmbUrl);
    expect($entity->has_map_url)->toBe('https://maps.google.com/?q=Test');
    expect($entity->entity_faqs)->toBeArray()->toHaveCount(1);
    expect($entity->same_as)->toBeArray()->toContain($gmbUrl);
});

it('renders FAQPage json-ld on public home when entity faqs exist', function () {
    if (! Schema::hasTable('seo_entities') || ! Schema::hasTable('business_profiles')) {
        $this->markTestSkipped('Growth Center SEO tables are not migrated.');
    }

    $profile = BusinessProfile::query()->create([
        'name' => 'Test Biz',
        'website' => config('app.url'),
        'email' => 't@example.com',
    ]);

    SeoEntity::query()->create([
        'business_profile_id' => $profile->id,
        'organization_name' => 'Test Biz',
        'entity_faqs' => [
            ['question' => 'Q1', 'answer' => 'A1'],
        ],
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('FAQPage', false);
});
