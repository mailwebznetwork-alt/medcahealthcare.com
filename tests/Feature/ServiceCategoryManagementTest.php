<?php

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Models\User;
use App\ModuleAccess;

it('creates a category and syncs many-to-many services', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $service = Service::factory()->create();

    $this->actingAs($user)
        ->post(route('operations.service-categories.store'), [
            'name' => 'Home Care',
            'code' => 'home-care',
            'description' => 'In-home nursing and attendant care.',
            'sort_order' => 5,
            'is_active' => '1',
        ])
        ->assertRedirect(route('operations.service-categories.index'));

    $category = ServiceCategory::query()->where('code', 'home-care')->first();
    expect($category)->not->toBeNull()
        ->and($category->name)->toBe('Home Care')
        ->and($category->is_active)->toBeTrue();

    $this->actingAs($user)
        ->put(route('operations.services.update', $service), [
            'title' => $service->title,
            'service_code' => $service->service_code,
            'publish_status' => $service->publish_status->value,
            'visibility' => $service->visibility->value,
            'category_ids' => [$category->id],
            'is_active' => '1',
        ])
        ->assertRedirect();

    expect($service->fresh()->categories->pluck('id')->all())->toContain($category->id);
});

it('filters services by multiple categories', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $catA = ServiceCategory::factory()->create(['name' => 'Cat A', 'code' => 'cat-a']);
    $catB = ServiceCategory::factory()->create(['name' => 'Cat B', 'code' => 'cat-b']);

    $inA = Service::factory()->create(['title' => 'Alpha Service']);
    $inB = Service::factory()->create(['title' => 'Beta Service']);
    $inA->categories()->attach($catA->id);
    $inB->categories()->attach($catB->id);

    $this->actingAs($user)
        ->get(route('operations.services.index', ['category_ids' => [$catA->id]]))
        ->assertSuccessful()
        ->assertSee('Alpha Service', false)
        ->assertDontSee('Beta Service', false);
});

it('shows public category page with paginated services', function () {
    $pin = PinCode::factory()->create(['pincode' => '560076', 'is_active' => true, 'is_serviceable' => true]);

    $category = ServiceCategory::factory()->create([
        'name' => 'Physiotherapy',
        'code' => 'physiotherapy',
        'is_active' => true,
    ]);

    $service = Service::factory()->create([
        'is_active' => true,
        'publish_status' => 'published',
        'visibility' => 'public',
    ]);
    $service->pincodes()->attach($pin->id);
    $service->categories()->attach($category->id);

    $this->withSession([
        config('location.session_key', 'medca.detected_pincode') => '560076',
    ])
        ->get(route('public.service-categories.show', $category->code))
        ->assertSuccessful()
        ->assertSee('Physiotherapy', false)
        ->assertSee($service->title, false);
});

it('soft deletes category and detaches services', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create();
    $service->categories()->attach($category->id);

    $this->actingAs($user)
        ->delete(route('operations.service-categories.destroy', $category))
        ->assertRedirect(route('operations.service-categories.index'));

    expect(ServiceCategory::query()->find($category->id))->toBeNull()
        ->and(ServiceCategory::withTrashed()->find($category->id))->not->toBeNull()
        ->and($service->fresh()->categories)->toBeEmpty();
});

it('renders category edit page with master tabs', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $category = ServiceCategory::factory()->create([
        'name' => 'Nursing',
        'code' => 'nursing',
    ]);

    $this->actingAs($user)
        ->get(route('operations.service-categories.edit', $category))
        ->assertSuccessful()
        ->assertSee('optimization hub', false)
        ->assertSee('Content', false)
        ->assertSee('Schema', false);
});

it('renders sub-service edit page with master tabs', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $service = Service::factory()->create();
    $sub = SubService::query()->create([
        'service_id' => $service->id,
        'sub_service_code' => 'home-visit',
        'title' => 'Home Visit',
        'is_active' => true,
        'publish_status' => 'published',
        'visibility' => 'public',
    ]);

    $this->actingAs($user)
        ->get(route('operations.services.sub-services.edit', [$service, $sub]))
        ->assertSuccessful()
        ->assertSee('Sub-service master', false)
        ->assertSee('Schema', false);
});

it('persists sort order when updating a category from the basic tab', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $category = ServiceCategory::factory()->create([
        'name' => 'Caregiver Services',
        'code' => 'cat-caregiver-services',
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->put(route('operations.service-categories.update', $category), [
            'name' => 'Caregiver Services',
            'code' => 'cat-caregiver-services',
            'sort_order' => 12,
            'publish_status' => 'published',
            'visibility' => 'public',
            'is_active' => '1',
        ])
        ->assertRedirect(route('operations.service-categories.edit', $category));

    expect($category->fresh()->sort_order)->toBe(12);
});

it('persists full catalog master fields when updating a category', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $category = ServiceCategory::factory()->create([
        'name' => 'Doctor Visits',
        'code' => 'doctor-visits',
    ]);

    $this->actingAs($user)
        ->put(route('operations.service-categories.update', $category), [
            'name' => 'Doctor Visits',
            'code' => 'doctor-visits',
            'publish_status' => 'published',
            'visibility' => 'public',
            'short_summary' => 'GP and specialist visits at home.',
            'description' => 'Full description for doctor visit category.',
            'key_benefits_lines' => "Convenient\nLicensed doctors",
            'seo' => [
                'meta_title' => 'Doctor Visits Bangalore',
                'meta_description' => 'Book doctor visits at home.',
                'h1' => 'Doctor Visits',
                'focus_keywords_lines' => "doctor visit\nhome doctor",
            ],
            'faqs' => [
                ['question' => 'Do you cover Arekere?', 'answer' => 'Yes, within 25km.'],
            ],
            'schema_type' => 'CollectionPage',
            'schema_json' => '{"@type":"CollectionPage","name":"Doctor Visits"}',
            'is_active' => '1',
        ])
        ->assertRedirect(route('operations.service-categories.edit', $category));

    $category->refresh()->load(['seo', 'faqs', 'schema']);

    expect($category->short_summary)->toBe('GP and specialist visits at home.')
        ->and($category->key_benefits)->toBe(['Convenient', 'Licensed doctors'])
        ->and($category->seo?->meta_title)->toBe('Doctor Visits Bangalore')
        ->and($category->seo?->h1)->toBe('Doctor Visits')
        ->and($category->faqs)->toHaveCount(1)
        ->and($category->schema)->not->toBeNull()
        ->and($category->optimization_snapshot)->toBeArray();
});

it('exposes category picker api for operations module', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    ServiceCategory::factory()->create(['code' => 'api-cat', 'is_active' => true]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/admin/operations/service-categories/picker')
        ->assertSuccessful()
        ->assertJsonFragment(['code' => 'api-cat']);
});
