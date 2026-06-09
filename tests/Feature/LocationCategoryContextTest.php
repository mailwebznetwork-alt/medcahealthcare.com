<?php

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Public\PinCodeCoverageUrlResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('appends category context to coverage area links', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560999',
        'area_name' => 'Arabic College',
        'is_active' => true,
        'geo_page_ready' => true,
    ]);

    $category = ServiceCategory::factory()->create([
        'code' => 'cat-lab',
        'name' => 'Medical Lab Services',
    ]);

    $url = app(PinCodeCoverageUrlResolver::class)->urlFor($pin, category: $category);

    expect($url)
        ->toContain('/locations/arabic-college')
        ->toContain('category=cat-lab');
});

it('renders category-scoped services and near you on location pages', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560999',
        'area_name' => 'Arabic College',
        'is_active' => true,
        'geo_page_ready' => true,
    ]);

    $otherPin = PinCode::factory()->create([
        'pincode' => '560998',
        'area_name' => 'Adugodi',
        'is_active' => true,
        'geo_page_ready' => true,
    ]);

    $category = ServiceCategory::factory()->create([
        'code' => 'cat-lab',
        'name' => 'Medical Lab Services',
    ]);

    $otherCategory = ServiceCategory::factory()->create([
        'code' => 'cat-care',
        'name' => 'Caregiver Services',
    ]);

    $labService = Service::factory()->create(['title' => 'Blood Tests at Home']);
    $labService->pincodes()->attach($pin->id);
    $labService->categories()->attach($category->id);

    $careService = Service::factory()->create(['title' => 'Bedridden Patient Care']);
    $careService->pincodes()->attach($pin->id);
    $careService->categories()->attach($otherCategory->id);

    $response = $this->get('/locations/arabic-college?category=cat-lab');

    $response->assertOk()
        ->assertSee('Medical Lab Services in Arabic College', false)
        ->assertSee('Blood Tests at Home', false)
        ->assertDontSee('Bedridden Patient Care', false)
        ->assertSee('data-section="near-you"', false)
        ->assertSee('Healthcare Categories in Arabic College', false);
});

it('preserves category context when redirecting to canonical location slug', function () {
    PinCode::factory()->create([
        'pincode' => '560997',
        'area_name' => 'Arabic College',
        'slug' => 'arabic-college-560997',
        'is_active' => true,
        'geo_page_ready' => true,
    ]);

    $this->get('/locations/arabic-college-560997?category=cat-lab')
        ->assertRedirect('/locations/arabic-college?category=cat-lab');
});

it('appends service context when no service-location page exists', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560996',
        'area_name' => 'JP Nagar',
        'is_active' => true,
        'geo_page_ready' => true,
    ]);

    $service = Service::factory()->create([
        'service_code' => 'SRV-LAB-1',
        'title' => 'Blood Tests at Home',
    ]);
    $service->pincodes()->attach($pin->id);

    $url = app(PinCodeCoverageUrlResolver::class)->urlFor($pin, service: $service);

    expect($url)
        ->toContain('/locations/jp-nagar')
        ->toContain('service=SRV-LAB-1');
});
