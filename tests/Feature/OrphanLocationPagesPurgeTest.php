<?php

use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Operations\ServiceLocationPageProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('purges generated location cms pages without service location mapping', function () {
    $orphan = Page::factory()->create([
        'slug' => 'service-elder-care-loc-921196',
        'title' => 'Elder Care in Grady Ports',
        'page_source' => 'generated',
        'registry_owner' => 'operations_location_matrix',
        'is_active' => true,
    ]);

    PageRegistry::query()->create([
        'page_id' => $orphan->id,
        'entity_type' => 'page',
        'entity_id' => $orphan->id,
        'registry_key' => 'page:'.$orphan->slug,
        'page_category' => 'location',
        'owner' => 'operations_location_matrix',
        'source' => 'generated',
        'public_path' => '/services/elder-care/grady-ports',
        'is_listed' => true,
    ]);

    $result = app(DownstreamArtifactPurger::class)->purgeOrphanLocationPages();

    expect($result['pages_removed'])->toBe(1)
        ->and(Page::query()->whereKey($orphan->id)->exists())->toBeFalse()
        ->and(PageRegistry::query()->where('registry_key', 'page:'.$orphan->slug)->exists())->toBeFalse();
});

it('does not purge location pages that still have a service location mapping', function () {
    $pin = PinCode::factory()->create(['pincode' => '560078', 'is_active' => true]);
    $service = Service::factory()->create([
        'service_code' => 'medical-lab',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    $service->pincodes()->attach($pin->id);

    app(ServiceLocationPageProvisioner::class)->provisionOne($service, $pin);

    $mapping = ServiceLocationPage::query()
        ->where('service_id', $service->id)
        ->where('pincode_id', $pin->id)
        ->firstOrFail();

    $result = app(DownstreamArtifactPurger::class)->purgeOrphanLocationPages();

    expect($result['pages_removed'])->toBe(0)
        ->and(Page::query()->whereKey($mapping->page_id)->exists())->toBeTrue();
});
