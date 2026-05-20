<?php

use App\Enums\PageLayoutMode;
use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Block;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceSeo;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Operations\ServiceDetailPageProvisioner;
use App\Services\Public\PublicPagePresenter;
use App\Services\Public\ServicesDetailPageResolver;

function serviceDetailOperationsUser(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);
}

it('injects service and blade variable name for detail pages', function () {
    $service = Service::factory()->create([
        'service_code' => 'caregivers',
        'title' => 'Caregivers',
    ]);

    $vars = app(PublicPagePresenter::class)->variablesForServiceDetail($service);

    expect($vars['service']->id)->toBe($service->id)
        ->and($vars['caregivers']->id)->toBe($service->id);
});

it('resolves detail page by service-{code} slug pattern without detail_page_id', function () {
    $service = Service::factory()->create([
        'service_code' => 'caregivers',
        'title' => 'Caregivers',
        'detail_page_id' => null,
    ]);

    Page::factory()->create([
        'slug' => 'service-caregivers',
        'is_active' => true,
        'content' => '{{block:pattern-hero}}',
        'layout_mode' => PageLayoutMode::Canvas,
    ]);

    Block::query()->create([
        'block_name' => 'Pattern hero',
        'block_slug' => 'pattern-hero',
        'code' => '<h1 data-pattern>{{ $caregivers->title }}</h1>{{service:caregivers}}',
        'is_active' => true,
    ]);

    $resolved = app(ServicesDetailPageResolver::class)->resolveFor($service);

    expect($resolved)->not->toBeNull()
        ->and($resolved->slug)->toBe('service-caregivers');

    $this->get('/services/caregivers')
        ->assertSuccessful()
        ->assertSee('data-pattern', false)
        ->assertSee('Caregivers', false);
});

it('creates and links a detail page via the provisioner', function () {
    $service = Service::factory()->create([
        'service_code' => 'home-nursing',
        'title' => 'Home Nursing',
        'detail_page_id' => null,
    ]);

    $page = app(ServiceDetailPageProvisioner::class)->provision($service);

    expect($page->slug)->toBe('service-home-nursing')
        ->and($page->usesCanvasLayout())->toBeTrue()
        ->and($service->fresh()->detail_page_id)->toBe($page->id)
        ->and($page->content)->toContain('service-detail-hero');

    expect(Block::query()->where('block_slug', 'service-detail-hero')->exists())->toBeTrue();
});

it('renders the operations service edit form without a view error', function () {
    $user = serviceDetailOperationsUser();
    $service = Service::factory()->create(['service_code' => 'caregivers']);

    $this->actingAs($user)
        ->get(route('operations.services.edit', $service))
        ->assertSuccessful()
        ->assertSee('Public page', false)
        ->assertSee('GEO', false);
});

it('uses page meta title on public service detail when a detail page is linked', function () {
    $service = Service::factory()->create([
        'service_code' => 'meta-test',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $service->seo()->updateOrCreate([], [
        'meta_title' => 'Legacy Service Meta',
    ]);

    $page = Page::factory()->create([
        'slug' => 'service-meta-test',
        'meta_title' => 'Page Primary Meta',
        'meta_description' => 'From the Site Architect page.',
        'is_active' => true,
        'content' => '{{block:meta-block}}',
        'layout_mode' => PageLayoutMode::Canvas,
    ]);

    $service->update(['detail_page_id' => $page->id]);

    Block::query()->create([
        'block_name' => 'Meta block',
        'block_slug' => 'meta-block',
        'code' => '<p data-meta-block>ok</p>',
        'is_active' => true,
    ]);

    $this->get('/services/meta-test')
        ->assertSuccessful()
        ->assertSee('Page Primary Meta', false)
        ->assertDontSee('Legacy Service Meta', false);
});

it('stores detail page from operations', function () {
    $user = serviceDetailOperationsUser();

    $service = Service::factory()->create([
        'service_code' => 'icu-at-home',
        'detail_page_id' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('operations.services.detail-page.store', $service));

    expect($service->fresh()->detail_page_id)->not->toBeNull();

    $pageId = $service->fresh()->detail_page_id;
    $response->assertRedirect(route('site-architect.pages.index', ['edit' => $pageId]));
    expect(Page::query()->where('slug', 'service-icu-at-home')->exists())->toBeTrue();
});
