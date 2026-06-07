<?php

use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Models\User;
use App\Services\Operations\ServiceMasterOrchestrator;
use App\ModuleAccess;

function siteArchitectPreviewUser(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::SITE_ARCHITECT])
            ->all(),
    ]);
}

it('previews a service detail page without error when blocks use $service', function () {
    $user = siteArchitectPreviewUser();

    Block::query()->updateOrCreate(
        ['block_slug' => 'service-detail-hero'],
        [
            'block_name' => 'Service detail — hero',
            'code' => '<h1 data-preview-hero>{{ $service->title }}</h1>',
            'is_active' => true,
        ]
    );

    $service = Service::factory()->create([
        'service_code' => 'homenursing-services',
        'title' => 'Home Nursing Services',
    ]);

    $page = Page::factory()->create([
        'slug' => 'service-homenursing-services',
        'content' => '{{block:service-detail-hero}}',
        'layout_mode' => PageLayoutMode::Canvas,
        'is_active' => true,
    ]);

    $service->update(['detail_page_id' => $page->id]);

    $this->actingAs($user)
        ->get(route('site-architect.pages.preview', $page))
        ->assertSuccessful()
        ->assertSee('data-preview-hero', false)
        ->assertSee('Home Nursing Services', false);
});

it('previews a service location page with the same public url context', function () {
    $user = siteArchitectPreviewUser();

    $service = Service::factory()->create(['service_code' => 'medical-lab', 'title' => 'Medical Lab']);
    $pin = PinCode::factory()->create(['pincode' => '560102', 'area_name' => 'HSR Layout', 'is_active' => true]);
    $service->pincodes()->attach($pin->id);

    app(ServiceMasterOrchestrator::class)->sync($service->fresh(['pincodes']));

    $mapping = ServiceLocationPage::query()->where('service_id', $service->id)->first();
    expect($mapping)->not->toBeNull();

    $publicUrl = $mapping->publicUrl();
    expect($publicUrl)->toEndWith('/services/medical-lab/'.$mapping->location_slug);

    $this->actingAs($user)
        ->get(route('site-architect.pages.preview', $mapping->page))
        ->assertSuccessful();

    $this->get($publicUrl)->assertOk();
});
