<?php

use App\Enums\PageLayoutMode;
use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Block;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Services\Content\ServiceBindingRegistry;
use App\Services\ContentParser;
use App\Services\UserLocationService;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    app(ServiceBindingRegistry::class)->flush();
});

it('deduplicates service binding queries within one request', function () {
    Service::factory()->create([
        'service_code' => 'caregivers',
        'title' => 'Caregivers',
    ]);

    Block::query()->create([
        'block_name' => 'Dup test',
        'block_slug' => 'dup-services',
        'code' => '{{block:dup-a}}{{block:dup-b}}',
        'is_active' => true,
    ]);

    Block::query()->create([
        'block_name' => 'Dup A',
        'block_slug' => 'dup-a',
        'code' => '{{ $caregivers->title }}{{service:caregivers}}',
        'is_active' => true,
    ]);

    Block::query()->create([
        'block_name' => 'Dup B',
        'block_slug' => 'dup-b',
        'code' => '{{ $caregivers->title }}{{service:caregivers}}',
        'is_active' => true,
    ]);

    expect(DB::getQueryLog())->toBe([]);

    DB::enableQueryLog();
    $html = ContentParser::parse('{{block:dup-services}}');
    $queries = collect(DB::getQueryLog())
        ->filter(fn (array $q): bool => str_contains($q['query'], 'from "services"'))
        ->count();
    DB::disableQueryLog();

    expect($html)->toContain('CaregiversCaregivers');
    expect($queries)->toBe(1);
});

it('exposes publishedServices on the services hub page', function () {
    $pin = PinCode::factory()->create(['pincode' => '560076', 'is_active' => true, 'is_serviceable' => true]);
    $service = Service::factory()->create([
        'service_code' => 'hub-listed',
        'title' => 'Hub Listed Service',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
    ]);
    $service->pincodes()->attach($pin->id);

    app(UserLocationService::class)->rememberPincode('560076');

    Block::query()->updateOrCreate(
        ['block_slug' => 'services-published-count'],
        [
            'block_name' => 'Published count',
            'code' => '<p data-test="published">{{ $publishedServices->count() }} services</p>',
            'is_active' => true,
        ]
    );

    Page::query()->updateOrCreate(
        ['slug' => 'services'],
        [
            'title' => 'Services',
            'content' => '{{block:services-published-count}}',
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    $this->get('/services')
        ->assertSuccessful()
        ->assertSee('data-test="published"', false)
        ->assertSee('1 services', false);
});

it('renders service detail via shared template with procedures from database', function () {
    Page::query()->updateOrCreate(
        ['slug' => 'services-detail-template'],
        [
            'title' => 'Service detail',
            'content' => '{{block:services-detail-layout}}',
            'is_active' => true,
            'layout_mode' => PageLayoutMode::Canvas,
        ]
    );

    Block::query()->updateOrCreate(
        ['block_slug' => 'services-detail-layout'],
        [
            'block_name' => 'Detail',
            'code' => '@include(\'public.services.partials.service-detail-carousel\', [\'service\' => $service])',
            'is_active' => true,
        ]
    );

    $service = Service::factory()->create([
        'service_code' => 'carousel-db',
        'title' => 'Carousel DB Service',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'procedures' => ['Custom procedure A', 'Custom procedure B'],
    ]);

    $this->get('/services/carousel-db')
        ->assertSuccessful()
        ->assertSee('Custom procedure A', false)
        ->assertSee('data-service-detail="carousel-db"', false);
});

it('renders carousel partial for selected service tokens in a block', function () {
    $a = Service::factory()->create([
        'service_code' => 'svc-a',
        'title' => 'Service Alpha',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
    ]);
    Service::factory()->create([
        'service_code' => 'svc-b',
        'title' => 'Service Beta Hidden',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
    ]);

    Block::query()->updateOrCreate(
        ['block_slug' => 'pick-a-only'],
        [
            'block_name' => 'Pick A',
            'code' => "{{service:svc-a}}\n@include('public.services.partials.services-carousel', ['services' => \$services])",
            'is_active' => true,
        ]
    );

    $html = ContentParser::parse('{{block:pick-a-only}}');

    expect($html)->toContain('Service Alpha')
        ->and($html)->not->toContain('Service Beta Hidden');
});
