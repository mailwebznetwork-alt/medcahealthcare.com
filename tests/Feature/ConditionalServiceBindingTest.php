<?php

use App\Enums\PublishStatus;
use App\Enums\ServiceVisibility;
use App\Models\Block;
use App\Models\Service;
use App\Services\Content\BlockBoundServicesResolver;
use App\Services\Content\ServiceBindingResolver;
use App\Services\ContentParser;
use App\Services\Operations\ServiceInternalLinkingEngine;

it('falls back to live catalog when carousel block tokens are stale', function () {
    Service::factory()->count(3)->create([
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'is_active' => true,
    ]);

    Block::query()->create([
        'block_name' => 'Carousel',
        'block_slug' => 'services-block-carousel',
        'code' => "{{service:homenursing-services}}\n{{service:elder-care}}\n@include('blocks.services.block-carousel')",
        'is_active' => true,
    ]);

    $rendered = ContentParser::parse('{{block:services-block-carousel}}');

    expect($rendered)->toContain('data-layout="services-carousel"');
});

it('uses live service headline after title changes in carousel output', function () {
    $service = Service::factory()->create([
        'title' => 'Original Title',
        'service_code' => 'SRV-LIVE-1',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
    ]);

    Block::query()->create([
        'block_name' => 'Carousel',
        'block_slug' => 'services-block-carousel',
        'code' => "{{service:SRV-LIVE-1}}\n@include('blocks.services.block-carousel')",
        'is_active' => true,
    ]);

    $service->forceFill(['title' => 'Renamed In Database'])->save();
    $service->seo?->forceFill(['h1' => 'Renamed In Database'])->save();

    $rendered = ContentParser::parse('{{block:services-block-carousel}}');

    expect($rendered)->toContain('Renamed In Database')
        ->not->toContain('Original Title');
});

it('resolves configured legacy aliases for service tokens', function () {
    Service::factory()->create([
        'service_code' => 'SRV-CAREGIVER-1',
        'title' => 'Elderly Care',
        'is_active' => true,
    ]);

    config()->set('service_bindings.aliases', [
        'elder-care' => 'SRV-CAREGIVER-1',
    ]);

    $resolved = app(ServiceBindingResolver::class)->resolveForBlock('elder-care');

    expect($resolved)->not->toBeNull()
        ->and($resolved->service_code)->toBe('SRV-CAREGIVER-1');
});

it('resolves service tokens case-insensitively', function () {
    Service::factory()->create([
        'service_code' => 'SRV-LAB-1',
        'is_active' => true,
    ]);

    $resolved = app(ServiceBindingResolver::class)->resolveForBlock('srv-lab-1');

    expect($resolved)->not->toBeNull()
        ->and($resolved->service_code)->toBe('SRV-LAB-1');
});

it('falls back to category peers when related service codes are stale', function () {
    $category = \App\Models\ServiceCategory::factory()->create();

    $primary = Service::factory()->create([
        'service_code' => 'SRV-PRIMARY',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
        'custom_fields' => [
            'related_service_codes' => 'elder-care,caregivers',
        ],
    ]);

    $peer = Service::factory()->create([
        'service_code' => 'SRV-PEER',
        'title' => 'Peer Service',
        'publish_status' => PublishStatus::Published,
        'visibility' => ServiceVisibility::Public,
    ]);

    $primary->categories()->attach($category->id);
    $peer->categories()->attach($category->id);

    $links = app(ServiceInternalLinkingEngine::class)->build($primary->fresh(['categories']));

    expect($links['related_services'])->not->toBeEmpty()
        ->and(collect($links['related_services'])->pluck('code'))->toContain('SRV-PEER');
});

it('preserves token order in block bound services collection', function () {
    $first = Service::factory()->create(['service_code' => 'SRV-A', 'is_active' => true]);
    $second = Service::factory()->create(['service_code' => 'SRV-B', 'is_active' => true]);

    $ordered = app(BlockBoundServicesResolver::class)->orderedFromTokens(
        "{{service:SRV-B}}\n{{service:SRV-A}}"
    );

    expect($ordered->pluck('service_code')->all())->toBe(['SRV-B', 'SRV-A']);
});
