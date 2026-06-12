<?php

use App\Enums\PageLayoutMode;
use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Block;
use App\Models\Page;
use App\Models\Service;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Operations\ServiceDetailPageProvisioner;
use App\Services\Public\PublicPagePresenter;
use App\Services\Public\ServicesDetailPageResolver;
use App\Support\BlockContent;

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
        ->and($resolved->slug)->toBe('service-caregivers')
        ->and($service->fresh()->detail_page_id)->toBe($resolved->id);

    $this->get('/services/caregivers')
        ->assertSuccessful()
        ->assertSee('data-pattern', false)
        ->assertSee('Caregivers', false);
});

it('relinks an orphan service detail page without running full provision on first request', function () {
    $service = Service::factory()->create([
        'service_code' => 'SRV-home-physiotherapy',
        'title' => 'Home Physiotherapy',
        'detail_page_id' => null,
    ]);

    $page = Page::factory()->create([
        'slug' => 'service-SRV-home-physiotherapy',
        'is_active' => true,
        'content' => '{{block:service-detail-hero}}',
        'layout_mode' => PageLayoutMode::Canvas,
    ]);

    Block::query()->create([
        'block_name' => 'Service detail hero',
        'block_slug' => 'service-detail-hero',
        'code' => '<h1>{{ $service->title }}</h1>',
        'is_active' => true,
    ]);

    expect(app(ServicesDetailPageResolver::class)->resolveFor($service->fresh())?->id)->toBe($page->id)
        ->and($service->fresh()->detail_page_id)->toBe($page->id);

    $this->get('/services/SRV-home-physiotherapy')
        ->assertSuccessful()
        ->assertSee('Home Physiotherapy', false);
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
        ->and($page->content)->toContain('service-detail-hero')
        ->and($page->content)->toContain('service-detail-body');

    expect(Block::query()->where('block_slug', 'service-detail-hero')->exists())->toBeTrue();
    expect(Block::query()->where('block_slug', 'service-detail-body')->exists())->toBeTrue();
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

it('shows edit blocks button when a detail page is already linked', function () {
    $user = serviceDetailOperationsUser();
    $page = Page::factory()->create(['slug' => 'service-caregivers', 'is_active' => true]);
    $service = Service::factory()->create([
        'service_code' => 'caregivers',
        'detail_page_id' => $page->id,
    ]);

    $this->actingAs($user)
        ->get(route('operations.services.edit', $service))
        ->assertSuccessful()
        ->assertSee('Edit blocks', false)
        ->assertSee('service-caregivers', false);
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

it('shows each service own title not shared block section content', function () {
    $caregivers = Service::factory()->create([
        'service_code' => 'caregivers',
        'title' => 'Caregiver Services at Home',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);
    $nursing = Service::factory()->create([
        'service_code' => 'home-nursing',
        'title' => 'Home Nursing',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    $hero = Block::query()->where('block_slug', 'service-detail-hero')->first()
        ?? Block::query()->create([
            'block_slug' => 'service-detail-hero',
            'block_name' => 'Hero',
            'code' => "@include('blocks.services.service-detail-hero')",
            'is_active' => true,
        ]);
    $settings = is_array($hero->settings_json) ? $hero->settings_json : [];
    $settings['content'] = [
        'headline' => 'Caregiver Services at Home',
        'subheadline' => 'Wrong shared copy',
    ];
    $hero->update(['settings_json' => $settings]);

    app(ServiceDetailPageProvisioner::class)->provision($nursing);

    $this->get('/services/home-nursing')
        ->assertSuccessful()
        ->assertSee('Home Nursing', false)
        ->assertDontSee('Caregiver Services at Home', false);
});

it('uses service title when schema headline default is empty', function () {
    expect(BlockContent::get([], 'service-detail-hero', 'headline', 'Geriatric Care at Home'))
        ->toBe('Geriatric Care at Home');
});

it('renders database service fields in the detail body block', function () {
    $service = Service::factory()->create([
        'service_code' => 'wound-care-detail',
        'title' => 'Wound Care',
        'description' => '<p>Professional wound dressing at home.</p>',
        'key_benefits' => ['Sterile technique', 'Daily monitoring'],
        'eligibility' => ['Post-surgery patients'],
        'process_steps' => ['Book visit', 'Nurse assignment', 'Ongoing care'],
        'trust_signals' => ['Certified nurses'],
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    app(ServiceDetailPageProvisioner::class)->provision($service);

    $this->get('/services/wound-care-detail')
        ->assertSuccessful()
        ->assertSee('data-service-detail-body', false)
        ->assertSee('Key benefits', false)
        ->assertSee('Sterile technique', false)
        ->assertSee('How it works', false)
        ->assertSee('Book visit', false)
        ->assertSee('Who is this for?', false)
        ->assertSee('Certified nurses', false);
});

it('renders service title instead of raw blade in the detail hero headline', function () {
    $service = Service::factory()->create([
        'service_code' => 'geriatric-care',
        'title' => 'Geriatric Care at Home',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    app(ServiceDetailPageProvisioner::class)->provision($service);

    $this->get('/services/geriatric-care')
        ->assertSuccessful()
        ->assertSee('Geriatric Care at Home', false)
        ->assertDontSee('{{ $service->seo', false);
});

it('auto-provisions a detail page for public service URLs when missing', function () {
    $service = Service::factory()->create([
        'service_code' => 'medical-lab',
        'title' => 'Medical Lab',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
        'detail_page_id' => null,
    ]);

    $this->get('/services/medical-lab')
        ->assertSuccessful()
        ->assertSee('data-service-detail-hero', false);

    expect(Page::query()->where('slug', 'service-medical-lab')->exists())->toBeTrue();
    expect($service->fresh()->detail_page_id)->not->toBeNull();
});

it('lists service detail pages in site architect pages query', function () {
    $service = Service::factory()->create([
        'service_code' => 'medical-lab',
        'title' => 'Medical Lab',
    ]);

    app(ServiceDetailPageProvisioner::class)->provision($service);

    expect(Page::query()->where('slug', 'service-medical-lab')->value('title'))->toBe('Medical Lab');
});

it('creates an owned detail page when a service is stored', function () {
    $user = serviceDetailOperationsUser();

    $this->actingAs($user)
        ->post(route('operations.services.store'), [
            'title' => 'Wound Care',
            'service_code' => 'wound-care',
            'publish_status' => 'published',
            'visibility' => 'public',
            'is_active' => true,
        ])
        ->assertSessionDoesntHaveErrors();

    $service = Service::query()->where('service_code', 'wound-care')->firstOrFail();
    $page = Page::query()->where('slug', 'service-wound-care')->first();

    expect($page)->not->toBeNull()
        ->and($service->detail_page_id)->toBe($page->id)
        ->and($page->title)->toBe('Wound Care');
});

it('updates the owned detail page when a service is edited', function () {
    $user = serviceDetailOperationsUser();
    $service = Service::factory()->create([
        'service_code' => 'physio-a',
        'title' => 'Physio A',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);
    app(ServiceDetailPageProvisioner::class)->syncFromService($service);

    $this->actingAs($user)
        ->put(route('operations.services.update', $service), [
            'title' => 'Physiotherapy Plus',
            'service_code' => 'physio-a',
            'publish_status' => 'published',
            'visibility' => 'public',
            'is_active' => true,
        ])
        ->assertSessionDoesntHaveErrors();

    expect(Page::query()->where('slug', 'service-physio-a')->value('title'))->toBe('Physiotherapy Plus');
});

it('deletes the owned detail page when a service is deleted', function () {
    $user = serviceDetailOperationsUser();
    $service = Service::factory()->create([
        'service_code' => 'temp-remove',
        'title' => 'Temp Remove',
    ]);
    app(ServiceDetailPageProvisioner::class)->syncFromService($service);
    $pageId = Page::query()->where('slug', 'service-temp-remove')->value('id');

    $this->actingAs($user)
        ->delete(route('operations.services.destroy', $service))
        ->assertRedirect(route('operations.services.index'));

    expect(Service::query()->where('service_code', 'temp-remove')->exists())->toBeFalse()
        ->and(Page::query()->whereKey($pageId)->exists())->toBeFalse();
});

it('renames the detail page slug when service code changes', function () {
    $user = serviceDetailOperationsUser();
    $service = Service::factory()->create([
        'service_code' => 'old-code',
        'title' => 'Old Code',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);
    app(ServiceDetailPageProvisioner::class)->syncFromService($service);
    $pageId = Page::query()->where('slug', 'service-old-code')->value('id');

    $this->actingAs($user)
        ->put(route('operations.services.update', $service), [
            'title' => 'New Code',
            'service_code' => 'new-code',
            'publish_status' => 'published',
            'visibility' => 'public',
            'is_active' => true,
        ])
        ->assertSessionDoesntHaveErrors();

    expect(Page::query()->whereKey($pageId)->value('slug'))->toBe('service-new-code');
});

it('stores detail page from operations', function () {
    $user = serviceDetailOperationsUser();

    $service = Service::factory()->create([
        'service_code' => 'icu-at-home',
        'detail_page_id' => null,
    ]);

    $response = $this->actingAs($user)
        ->get(route('operations.services.detail-page.create', $service))
        ->assertRedirect(route('operations.services.edit', $service));

    expect($service->fresh()->detail_page_id)->not->toBeNull();
    expect(Page::query()->where('slug', 'service-icu-at-home')->exists())->toBeTrue();
});
