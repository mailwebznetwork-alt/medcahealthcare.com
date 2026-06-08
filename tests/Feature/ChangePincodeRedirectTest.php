<?php

use App\Livewire\Location\PincodeModal;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Discovery\ChangePincodeEngine;
use App\Services\Discovery\PincodeRedirectResolver;
use Illuminate\Http\Request;
use Livewire\Livewire;

it('redirects service-location pages to the selected pincode location url', function () {
    $btm = PinCode::factory()->create([
        'pincode' => '560030',
        'area_name' => 'BTM 2nd Stage',
        'is_active' => true,
        'is_serviceable' => true,
    ]);

    $arekere = PinCode::factory()->create([
        'pincode' => '560076',
        'area_name' => 'Arekere',
        'is_active' => true,
        'is_serviceable' => true,
    ]);

    $service = Service::factory()->create([
        'service_code' => 'elder-care',
        'title' => 'Elder Care at Home',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    $service->pincodes()->attach([$btm->id, $arekere->id]);

    $btmPage = Page::factory()->create(['is_active' => true]);
    $arekerePage = Page::factory()->create(['is_active' => true]);

    $btmMapping = ServiceLocationPage::query()->create([
        'service_id' => $service->id,
        'pincode_id' => $btm->id,
        'page_id' => $btmPage->id,
        'slug' => 'service-elder-care-loc-560030',
        'location_slug' => 'btm-2nd-stage',
        'is_indexable' => true,
    ]);

    $arekereMapping = ServiceLocationPage::query()->create([
        'service_id' => $service->id,
        'pincode_id' => $arekere->id,
        'page_id' => $arekerePage->id,
        'slug' => 'service-elder-care-loc-560076',
        'location_slug' => 'arekere',
        'is_indexable' => true,
    ]);

    $request = Request::create('/services/elder-care/btm-2nd-stage', 'GET');
    $redirectUrl = app(PincodeRedirectResolver::class)->resolveAfterSwitch($request, '560076');

    expect($redirectUrl)->toBe($arekereMapping->fresh()->publicUrl());
});

it('returns discovery payload with redirect url when switching pincode', function () {
    $pin = PinCode::factory()->create([
        'pincode' => '560076',
        'area_name' => 'Arekere',
        'is_active' => true,
        'is_serviceable' => true,
    ]);

    $service = Service::factory()->create([
        'service_code' => 'home-nursing',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    $service->pincodes()->attach($pin->id);

    $page = Page::factory()->create(['is_active' => true]);
    ServiceLocationPage::query()->create([
        'service_id' => $service->id,
        'pincode_id' => $pin->id,
        'page_id' => $page->id,
        'slug' => 'service-home-nursing-loc-560076',
        'location_slug' => 'arekere',
        'is_indexable' => true,
    ]);

    $request = Request::create('/services/home-nursing/bannerghatta', 'GET');
    $result = app(ChangePincodeEngine::class)->switch('560076', $request);

    expect($result['success'])->toBeTrue()
        ->and($result['redirect_url'])->toContain('/services/home-nursing/arekere');
});

it('redirects from the pincode modal on a service-location page', function () {
    $btm = PinCode::factory()->create([
        'pincode' => '560030',
        'area_name' => 'BTM 2nd Stage',
        'is_active' => true,
        'is_serviceable' => true,
    ]);

    $arekere = PinCode::factory()->create([
        'pincode' => '560076',
        'area_name' => 'Arekere',
        'is_active' => true,
        'is_serviceable' => true,
    ]);

    $service = Service::factory()->create([
        'service_code' => 'elder-care',
        'publish_status' => 'published',
        'visibility' => 'public',
        'is_active' => true,
    ]);
    $service->pincodes()->attach([$btm->id, $arekere->id]);

    $btmPage = Page::factory()->create(['is_active' => true]);
    $arekerePage = Page::factory()->create(['is_active' => true]);

    ServiceLocationPage::query()->create([
        'service_id' => $service->id,
        'pincode_id' => $btm->id,
        'page_id' => $btmPage->id,
        'slug' => 'service-elder-care-loc-560030',
        'location_slug' => 'btm-2nd-stage',
        'is_indexable' => true,
    ]);

    $arekereMapping = ServiceLocationPage::query()->create([
        'service_id' => $service->id,
        'pincode_id' => $arekere->id,
        'page_id' => $arekerePage->id,
        'slug' => 'service-elder-care-loc-560076',
        'location_slug' => 'arekere',
        'is_indexable' => true,
    ]);

    Livewire::test(PincodeModal::class)
        ->set('redirectContextPath', '/services/elder-care/btm-2nd-stage')
        ->set('pincode', '560076')
        ->call('savePincode')
        ->assertRedirect($arekereMapping->fresh()->publicUrl());
});
