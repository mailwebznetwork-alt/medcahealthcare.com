<?php

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Livewire\SiteArchitect\BlockFactory;
use App\Livewire\SiteArchitect\Pages;
use App\Models\Block;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceFaq;
use App\Models\ServiceSchema;
use App\Models\User;
use App\Services\ContentParser;
use App\Services\ServiceContextCollector;
use Livewire\Livewire;

beforeEach(function (): void {
    app(ServiceContextCollector::class)->reset();
});

it('renders {{service:CODE}} as the value of $services collection inside a block', function () {
    $service = Service::factory()->create([
        'title' => 'Caregivers At Home',
        'service_code' => 'caregivers',
    ]);

    Block::query()->create([
        'block_name' => 'Service grid',
        'block_slug' => 'service-grid',
        'code' => '<ul>@foreach($services as $svc)<li data-svc="{{ $svc->service_code }}">{{ $svc->title }}</li>@endforeach</ul>{{service:caregivers}}',
        'is_active' => true,
    ]);

    $rendered = ContentParser::parse('{{block:service-grid}}');

    expect($rendered)->toContain('<li data-svc="caregivers">Caregivers At Home</li>')
        ->and($rendered)->not->toContain('{{service:caregivers}}');
});

it('exposes the service as a Blade variable named after the service_code', function () {
    Service::factory()->create([
        'title' => 'Home Nursing',
        'service_code' => 'home-nursing',
    ]);

    Block::query()->create([
        'block_name' => 'Per service block',
        'block_slug' => 'per-service-block',
        'code' => '<h2>{{ $home_nursing->title }}</h2>{{service:home-nursing}}',
        'is_active' => true,
    ]);

    $rendered = ContentParser::parse('{{block:per-service-block}}');

    expect($rendered)->toContain('<h2>Home Nursing</h2>')
        ->and($rendered)->not->toContain('{{service:home-nursing}}');
});

it('does not auto-render service markup — the system only injects data, not layout', function () {
    Service::factory()->create([
        'service_code' => 'naked-service',
        'title' => 'Naked Service',
        'short_summary' => 'This summary should NEVER appear unless the admin Blade prints it.',
    ]);

    Block::query()->create([
        'block_name' => 'Layout owner',
        'block_slug' => 'layout-owner',
        'code' => '<section class="card">CONTROLLED</section>{{service:naked-service}}',
        'is_active' => true,
    ]);

    $rendered = ContentParser::parse('{{block:layout-owner}}');

    expect($rendered)
        ->toContain('<section class="card">CONTROLLED</section>')
        ->not->toContain('Naked Service')
        ->not->toContain('This summary should NEVER appear');
});

it('skips service tokens that reference a draft, inactive, or private service', function () {
    Service::factory()->create([
        'service_code' => 'draft-only',
        'publish_status' => PublishStatus::Draft,
    ]);
    Service::factory()->create([
        'service_code' => 'inactive-only',
        'is_active' => false,
        'publish_status' => PublishStatus::Published,
    ]);
    Service::factory()->create([
        'service_code' => 'private-only',
        'visibility' => ServiceVisibility::Private,
        'publish_status' => PublishStatus::Published,
    ]);

    Block::query()->create([
        'block_name' => 'Variants',
        'block_slug' => 'variants',
        'code' => '<x>[{{ $draft_only->title ?? "" }}]|[{{ $inactive_only->title ?? "" }}]|[{{ $private_only->title ?? "" }}]|OK</x>',
        'is_active' => true,
    ]);

    $rendered = ContentParser::parse('{{block:variants}}{{service:draft-only}}');

    expect($rendered)
        ->toContain('<x>[]|[]|[]|OK</x>');

    expect(app(ServiceContextCollector::class)->collected())->toHaveCount(0);
});

it('registers a service with the ServiceContextCollector when its token is rendered', function () {
    Service::factory()->create([
        'service_code' => 'registered-svc',
        'title' => 'Registered Service',
    ]);

    Block::query()->create([
        'block_name' => 'Reg block',
        'block_slug' => 'reg-block',
        'code' => '<div>{{ $registered_svc->title }}</div>{{service:registered-svc}}',
        'is_active' => true,
    ]);

    ContentParser::parse('{{block:reg-block}}');

    $collector = app(ServiceContextCollector::class);
    expect($collector->has('registered-svc'))->toBeTrue()
        ->and($collector->collected())->toHaveCount(1);
});

it('preregisters services from page content without rendering', function () {
    Service::factory()->create(['service_code' => 'pre-svc-1']);
    Service::factory()->create(['service_code' => 'pre-svc-2']);

    Block::query()->create([
        'block_name' => 'Pre block',
        'block_slug' => 'pre-block',
        'code' => '<div>FILLER</div>{{service:pre-svc-1}}{{service:pre-svc-2}}',
        'is_active' => true,
    ]);

    ContentParser::preregister('{{block:pre-block}}');

    $collector = app(ServiceContextCollector::class);
    expect($collector->has('pre-svc-1'))->toBeTrue()
        ->and($collector->has('pre-svc-2'))->toBeTrue()
        ->and($collector->count())->toBe(2);
});

it('emits Service Schema.org JSON-LD into the public page <head> when a block uses {{service:CODE}}', function () {
    $service = Service::factory()->create([
        'title' => 'Lab At Home',
        'service_code' => 'lab-at-home',
        'short_summary' => 'Phlebotomist visits your home.',
    ]);

    $service->seo()->updateOrCreate(
        ['service_id' => $service->id],
        ['meta_description' => 'Quick lab tests at home.']
    );

    $pin = PinCode::query()->create([
        'pincode' => '560076',
        'area_name' => 'Arekere',
        'city' => 'Bengaluru',
        'is_serviceable' => true,
        'is_active' => true,
    ]);
    $service->pincodes()->sync([$pin->id]);

    Block::query()->create([
        'block_name' => 'Lab block',
        'block_slug' => 'lab-block',
        'code' => '<div>Lab</div>{{service:lab-at-home}}',
        'is_active' => true,
    ]);

    $page = Page::factory()->create([
        'slug' => 'lab-services',
        'is_active' => true,
        'content' => '{{block:lab-block}}',
    ]);

    expect($page)->not->toBeNull();

    $response = $this->get('/p/lab-services');
    $response->assertSuccessful()
        ->assertSee('"@type":"Service"', false)
        ->assertSee('"name":"Lab At Home"', false)
        ->assertSee('"560076"', false);
});

it('emits FAQPage JSON-LD aggregated from collected services FAQs', function () {
    $service = Service::factory()->create([
        'service_code' => 'faq-svc',
        'title' => 'FAQ Service',
    ]);

    ServiceFaq::factory()->create([
        'service_id' => $service->id,
        'question' => 'Do you serve nights?',
        'answer' => 'Yes, 24x7 in Bengaluru.',
    ]);

    Block::query()->create([
        'block_name' => 'Faq block',
        'block_slug' => 'faq-block',
        'code' => '<div>FAQ</div>{{service:faq-svc}}',
        'is_active' => true,
    ]);

    Page::factory()->create([
        'slug' => 'faq-page',
        'is_active' => true,
        'content' => '{{block:faq-block}}',
    ]);

    $this->get('/p/faq-page')
        ->assertSuccessful()
        ->assertSee('"@type":"FAQPage"', false)
        ->assertSee('Do you serve nights?', false)
        ->assertSee('24x7 in Bengaluru.', false);
});

it('emits the service custom Schema JSON when defined on the service_schema row', function () {
    $service = Service::factory()->create([
        'service_code' => 'custom-schema',
        'title' => 'Custom Schema Service',
    ]);

    ServiceSchema::factory()->create([
        'service_id' => $service->id,
        'schema_type' => 'MedicalProcedure',
        'schema_json' => [
            '@type' => 'MedicalProcedure',
            'name' => 'Wound dressing',
        ],
    ]);

    Block::query()->create([
        'block_name' => 'Custom schema block',
        'block_slug' => 'custom-schema-block',
        'code' => '<div>X</div>{{service:custom-schema}}',
        'is_active' => true,
    ]);

    Page::factory()->create([
        'slug' => 'custom-schema-page',
        'is_active' => true,
        'content' => '{{block:custom-schema-block}}',
    ]);

    $this->get('/p/custom-schema-page')
        ->assertSuccessful()
        ->assertSee('"@type":"MedicalProcedure"', false)
        ->assertSee('Wound dressing', false);
});

it('reuses the same service across multiple blocks without duplicating Schema.org emits', function () {
    Service::factory()->create([
        'service_code' => 'shared-svc',
        'title' => 'Shared Service',
    ]);

    Block::query()->create([
        'block_name' => 'Block A',
        'block_slug' => 'shared-a',
        'code' => '<div>A:{{ $shared_svc->title }}</div>{{service:shared-svc}}',
        'is_active' => true,
    ]);
    Block::query()->create([
        'block_name' => 'Block B',
        'block_slug' => 'shared-b',
        'code' => '<div>B:{{ $shared_svc->title }}</div>{{service:shared-svc}}',
        'is_active' => true,
    ]);

    Page::factory()->create([
        'slug' => 'shared-page',
        'is_active' => true,
        'content' => "{{block:shared-a}}\n{{block:shared-b}}",
    ]);

    $response = $this->get('/p/shared-page');
    $response->assertSuccessful()
        ->assertSee('A:Shared Service', false)
        ->assertSee('B:Shared Service', false);

    expect(substr_count((string) $response->getContent(), '"name":"Shared Service"'))->toBe(1);
});

it('serves /services/{code} with the default fallback layout when no detail_page_id is set', function () {
    Service::factory()->create([
        'service_code' => 'default-route',
        'title' => 'Default Routed Service',
        'short_summary' => 'This is the fallback render.',
    ]);

    $this->get('/services/default-route')
        ->assertSuccessful()
        ->assertSee('Default Routed Service', false)
        ->assertSee('This is the fallback render.', false)
        ->assertSee('"@type":"Service"', false)
        ->assertSee('"name":"Default Routed Service"', false);
});

it('serves /services/{code} via the admin-linked detail page when detail_page_id is set', function () {
    $detailPage = Page::factory()->create([
        'slug' => 'caregivers-detail-layout',
        'is_active' => true,
        'content' => '{{block:caregivers-detail-block}}',
    ]);

    $service = Service::factory()->create([
        'service_code' => 'caregivers-routed',
        'title' => 'Caregivers Routed',
        'detail_page_id' => $detailPage->id,
    ]);

    Block::query()->create([
        'block_name' => 'Caregivers detail block',
        'block_slug' => 'caregivers-detail-block',
        'code' => '<section data-detail="caregivers">{{ $caregivers_routed->title }}</section>{{service:caregivers-routed}}',
        'is_active' => true,
    ]);

    expect($service->detail_page_id)->toBe($detailPage->id);

    $this->get('/services/caregivers-routed')
        ->assertSuccessful()
        ->assertSee('data-detail="caregivers"', false)
        ->assertSee('Caregivers Routed', false)
        ->assertSee('"@type":"Service"', false);
});

it('returns 404 for /services/{code} when the service is unpublished or missing', function () {
    Service::factory()->draft()->create([
        'service_code' => 'still-draft',
    ]);

    $this->get('/services/still-draft')->assertNotFound();
    $this->get('/services/never-existed')->assertNotFound();
});

it('lists active published services in the services sitemap segment', function () {
    Service::factory()->create([
        'service_code' => 'sitemap-public',
        'title' => 'Sitemap Public',
    ]);
    Service::factory()->draft()->create([
        'service_code' => 'sitemap-draft',
    ]);

    $this->get('/sitemap-services.xml')
        ->assertSuccessful()
        ->assertSee(url('/services/sitemap-public'))
        ->assertDontSee('/services/sitemap-draft');
});

it('block factory inserts a {{service:CODE}} token via Add service line', function () {
    Service::factory()->create([
        'service_code' => 'insertable-svc',
        'title' => 'Insertable Service',
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BlockFactory::class)
        ->call('startCreate')
        ->set('service_choice', 'insertable-svc')
        ->call('appendServiceToken')
        ->assertSet('code', '{{service:insertable-svc}}')
        ->assertSet('service_choice', '');
});

it('block factory lists draft services in the insert dropdown', function () {
    Service::factory()->draft()->create([
        'service_code' => 'draft-insert-svc',
        'title' => 'Draft Insert Label',
        'is_active' => true,
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BlockFactory::class)
        ->call('startCreate')
        ->assertSee('Draft Insert Label')
        ->assertSee('draft-insert-svc');
});

it('block factory allows inserting a token for a draft service', function () {
    Service::factory()->draft()->create([
        'service_code' => 'draft-token-svc',
        'title' => 'Draft Token',
        'is_active' => true,
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BlockFactory::class)
        ->call('startCreate')
        ->set('service_choice', 'draft-token-svc')
        ->call('appendServiceToken')
        ->assertSet('code', '{{service:draft-token-svc}}')
        ->assertHasNoErrors();
});

it('block factory omits inactive services from the insert dropdown', function () {
    Service::factory()->create([
        'service_code' => 'inactive-svc',
        'title' => 'Inactive Svc Unique Title XYZ',
        'is_active' => false,
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BlockFactory::class)
        ->call('startCreate')
        ->assertDontSee('Inactive Svc Unique Title XYZ');
});

it('pages block modal appends a {{service:CODE}} token to block_code', function () {
    Service::factory()->create([
        'service_code' => 'pages-modal-svc',
        'title' => 'Pages Modal Service',
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Pages::class)
        ->call('startCreate')
        ->call('addBlock')
        ->set('block_name', 'My block')
        ->set('block_code', '<div>existing</div>')
        ->set('service_choice', 'pages-modal-svc')
        ->call('appendServiceToken')
        ->assertSet('block_code', "<div>existing</div>\n{{service:pages-modal-svc}}")
        ->assertSet('service_choice', '');
});
