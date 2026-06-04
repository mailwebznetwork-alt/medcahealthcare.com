<?php

use App\Models\Module;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\User;
use App\Models\Vacancy;
use App\ModuleAccess;
use App\Services\DynamicModules\LegacyManagedModuleRegistry;
use Database\Seeders\LegacyManagedModuleSeeder;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('modules')) {
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_05_30_170000_create_dynamic_module_registry_tables.php']);
    }

    if (! Schema::hasColumn('services', 'custom_fields')) {
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_05_30_180000_add_custom_fields_to_legacy_managed_tables.php']);
    }

    $this->seed(LegacyManagedModuleSeeder::class);
});

function operationsAdmin(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'admin',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);
}

function operationsSchemaManager(): User
{
    return User::factory()->create([
        'name' => 'WDJERRIE',
        'email_verified_at' => now(),
        'role' => 'admin',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);
}

it('registers legacy services pin codes and job portal modules', function () {
    expect(Module::query()->where('slug', LegacyManagedModuleRegistry::SERVICES)->exists())->toBeTrue();
    expect(Module::query()->where('slug', LegacyManagedModuleRegistry::PIN_CODES)->value('table_name'))->toBe('pin_codes');
    expect(Module::query()->where('slug', LegacyManagedModuleRegistry::JOB_PORTAL)->value('table_name'))->toBe('vacancies');
});

it('hides custom fields section for non schema managers when no fields exist', function () {
    $user = operationsAdmin();

    $this->actingAs($user)
        ->get(route('operations.services.create'))
        ->assertOk()
        ->assertDontSee(__('Custom fields'), false)
        ->assertDontSee(__('Add field'), false);
});

it('shows unified custom fields table with add field for schema manager', function () {
    $user = operationsSchemaManager();

    $this->actingAs($user)
        ->get(route('operations.services.create'))
        ->assertOk()
        ->assertSee(__('Custom fields'), false)
        ->assertSee(__('Add field'), false)
        ->assertSee(__('No custom fields yet. Press Add field when you need to define one.'), false);
});

it('denies schema updates for non schema managers', function () {
    $user = operationsAdmin();
    $module = Module::query()->where('slug', LegacyManagedModuleRegistry::SERVICES)->firstOrFail();

    $this->actingAs($user)
        ->put(route('operations.managed-modules.fields.update', $module), [
            'fields' => [
                [
                    'label' => 'Warranty months',
                    'field_name' => 'warranty_months',
                    'field_type' => 'number',
                    'is_required' => '0',
                ],
            ],
        ])
        ->assertForbidden();
});

it('persists custom field schema and values for services', function () {
    $schemaManager = operationsSchemaManager();
    $user = operationsAdmin();
    $module = Module::query()->where('slug', LegacyManagedModuleRegistry::SERVICES)->firstOrFail();

    $this->actingAs($schemaManager)
        ->put(route('operations.managed-modules.fields.update', $module), [
            'fields' => [
                [
                    'label' => 'Warranty months',
                    'field_name' => 'warranty_months',
                    'field_type' => 'number',
                    'is_required' => '0',
                ],
            ],
        ])
        ->assertRedirect();

    $service = Service::factory()->create([
        'service_code' => 'SVC-CF-001',
        'publish_status' => 'draft',
        'visibility' => 'public',
    ]);

    $this->actingAs($user)
        ->put(route('operations.services.update', $service), [
            'title' => $service->title,
            'service_code' => $service->service_code,
            'price_range' => '999',
            'is_active' => '1',
            'is_featured' => '0',
            'publish_status' => 'draft',
            'visibility' => 'public',
            'sort_order' => 0,
            'detail_page_id' => '',
            'pincodes' => [],
            'custom_fields' => [
                'warranty_months' => '12',
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('operations.services.edit', $service));

    expect($service->fresh()->custom_fields)->toMatchArray(['warranty_months' => '12']);
});

it('allows saving a service without filling required custom field values', function () {
    $schemaManager = operationsSchemaManager();
    $user = operationsAdmin();
    $module = Module::query()->where('slug', LegacyManagedModuleRegistry::SERVICES)->firstOrFail();

    $this->actingAs($schemaManager)
        ->put(route('operations.managed-modules.fields.update', $module), [
            'fields' => [
                [
                    'label' => 'Internal note',
                    'field_name' => 'internal_note',
                    'field_type' => 'text',
                    'is_required' => '1',
                ],
            ],
        ])
        ->assertRedirect();

    $service = Service::factory()->create([
        'service_code' => 'SVC-OPT-CF',
        'publish_status' => 'draft',
        'visibility' => 'public',
    ]);

    $this->actingAs($user)
        ->put(route('operations.services.update', $service), [
            'title' => $service->title,
            'service_code' => $service->service_code,
            'price_range' => '999',
            'is_active' => '1',
            'is_featured' => '0',
            'publish_status' => 'draft',
            'visibility' => 'public',
            'sort_order' => 0,
            'detail_page_id' => '',
            'pincodes' => [],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('operations.services.edit', $service));

    expect($service->fresh()->custom_fields)->toBeEmpty();
});

it('shows value table for admin when fields are defined', function () {
    $schemaManager = operationsSchemaManager();
    $user = operationsAdmin();
    $module = Module::query()->where('slug', LegacyManagedModuleRegistry::SERVICES)->firstOrFail();

    $this->actingAs($schemaManager)
        ->put(route('operations.managed-modules.fields.update', $module), [
            'fields' => [
                [
                    'label' => 'Price note',
                    'field_name' => 'price_note',
                    'field_type' => 'text',
                    'is_required' => '0',
                ],
            ],
        ]);

    $this->actingAs($user)
        ->get(route('operations.services.create'))
        ->assertOk()
        ->assertSee(__('Custom fields'), false)
        ->assertSee('price_note', false)
        ->assertSee(__('Value'), false)
        ->assertDontSee(__('Save custom fields'), false);
});

it('stores pin code custom field values in json column', function () {
    $schemaManager = operationsSchemaManager();
    $user = operationsAdmin();
    $module = Module::query()->where('slug', LegacyManagedModuleRegistry::PIN_CODES)->firstOrFail();

    $this->actingAs($schemaManager)
        ->put(route('operations.managed-modules.fields.update', $module), [
            'fields' => [
                [
                    'label' => 'Zone tag',
                    'field_name' => 'zone_tag',
                    'field_type' => 'text',
                    'is_required' => '0',
                ],
            ],
        ]);

    $this->actingAs($user)
        ->post(route('operations.pin-codes.store'), [
            'pincode' => '560099',
            'area_name' => 'Test Area',
            'city' => 'Bangalore',
            'locality' => 'Test',
            'is_serviceable' => '1',
            'is_active' => '1',
            'delivery_charge' => '',
            'meta_title' => '',
            'meta_description' => '',
            'seo_keywords' => '',
            'slug' => '',
            'geo_page_ready' => '0',
            'custom_fields' => [
                'zone_tag' => 'south-zone',
            ],
        ])
        ->assertRedirect();

    $pinCode = PinCode::query()->where('pincode', '560099')->first();
    expect($pinCode?->custom_fields)->toMatchArray(['zone_tag' => 'south-zone']);
});

it('stores vacancy custom field values in json column', function () {
    $schemaManager = operationsSchemaManager();
    $user = operationsAdmin();
    $module = Module::query()->where('slug', LegacyManagedModuleRegistry::JOB_PORTAL)->firstOrFail();

    $this->actingAs($schemaManager)
        ->put(route('operations.managed-modules.fields.update', $module), [
            'fields' => [
                [
                    'label' => 'Shift note',
                    'field_name' => 'shift_note',
                    'field_type' => 'textarea',
                    'is_required' => '0',
                ],
            ],
        ]);

    $this->actingAs($user)
        ->post(route('operations.job-portal.vacancies.store'), [
            'title' => 'Staff Nurse',
            'department' => 'Nursing',
            'city' => 'Bangalore',
            'area' => 'Arekere',
            'pin_code' => '560076',
            'employment_type' => 'full_time',
            'visibility' => 'public',
            'workflow_status' => 'draft',
            'is_active' => '1',
            'sort_order' => 0,
            'custom_fields' => [
                'shift_note' => 'Night shift available',
            ],
        ])
        ->assertRedirect();

    $vacancy = Vacancy::query()->where('title', 'Staff Nurse')->first();
    expect($vacancy?->custom_fields)->toMatchArray(['shift_note' => 'Night shift available']);
});
