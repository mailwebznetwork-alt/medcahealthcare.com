<?php

use App\Models\BusinessProfile;
use App\Models\GrowthPincode;
use App\Models\PageElement;
use App\Models\PageSeo;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use Illuminate\Support\Facades\Schema;

it('serves global robots and llm endpoints', function () {
    if (! Schema::hasTable('seo_technical') || ! Schema::hasTable('business_profiles')) {
        $this->markTestSkipped('Growth Center global tables are not migrated.');
    }

    $profile = BusinessProfile::query()->create([
        'name' => 'MarkOnMinds',
        'website' => 'https://markonminds.test',
        'email' => 'hello@example.com',
    ]);

    SeoTechnical::query()->create([
        'business_profile_id' => $profile->id,
        'robots_txt' => "User-agent: *\nAllow: /\nSitemap: /sitemap.xml",
        'sitemap_enabled' => true,
        'canonical_url' => 'https://markonminds.test',
        'indexable' => true,
    ]);

    $this->get('/robots.txt')
        ->assertSuccessful()
        ->assertSeeText('User-agent: *');

    $this->get('/llm.txt')
        ->assertSuccessful()
        ->assertSeeText('GPTBot')
        ->assertSeeText('ClaudeBot');
});

it('serves sitemap and discovery payload using page and geo data', function () {
    if (! Schema::hasTable('business_profiles') || ! Schema::hasTable('page_seo') || ! Schema::hasTable('page_elements') || ! Schema::hasTable('pincodes')) {
        $this->markTestSkipped('Growth Center architecture tables are not migrated.');
    }

    $profile = BusinessProfile::query()->create([
        'name' => 'MarkOnMinds',
        'website' => 'https://markonminds.test',
        'email' => 'hello@example.com',
        'phone' => '9999999999',
    ]);

    SeoEntity::query()->create([
        'business_profile_id' => $profile->id,
        'organization_name' => 'MarkOnMinds',
        'meta_title' => 'Healthcare in Bangalore',
    ]);

    PageSeo::query()->create([
        'business_profile_id' => $profile->id,
        'page_slug' => 'services/home-care',
        'meta_title' => 'Home Care',
    ]);

    PageElement::query()->create([
        'page_slug' => 'services/home-care',
        'section' => 'hero',
        'key' => 'headline',
        'value' => 'Premium healthcare services',
        'type' => 'text',
    ]);

    GrowthPincode::query()->create([
        'business_profile_id' => $profile->id,
        'pincode' => '560076',
        'serviceable' => true,
        'landing_page' => '/locations/560076',
        'priority' => 'high',
    ]);

    $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertSee(url('/services/home-care'))
        ->assertSee(url('/locations/560076'));

    $this->getJson('/ai-discovery')
        ->assertSuccessful()
        ->assertJsonPath('services.0.slug', 'services/home-care')
        ->assertJsonPath('locations.0.pincode', '560076')
        ->assertJsonPath('business.organization_name', 'MarkOnMinds');
});
