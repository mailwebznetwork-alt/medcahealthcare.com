<?php

use App\Livewire\Location\PincodeModal;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\User;
use App\Services\UserLocationService;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

it('suggests pincodes by prefix in the location modal', function () {
    foreach (range(0, 9) as $i) {
        PinCode::factory()->create([
            'pincode' => '56007'.$i,
            'area_name' => 'Area '.$i,
            'is_active' => true,
        ]);
    }
    PinCode::factory()->create(['pincode' => '560100', 'is_active' => true]);

    Livewire::test(PincodeModal::class)
        ->set('open', true)
        ->set('pincode', '56007')
        ->assertSet('showPincodeSuggestions', true)
        ->assertCount('pincodeSuggestions', 10)
        ->call('selectPincode', '560075')
        ->assertSet('pincode', '560075')
        ->assertSet('showPincodeSuggestions', false);
});

it('shows cancel when changing an existing pincode', function () {
    PinCode::factory()->create(['pincode' => '560076', 'is_active' => true, 'is_serviceable' => true]);
    app(UserLocationService::class)->rememberPincode('560076');

    Livewire::test(PincodeModal::class)
        ->call('openModal', '/locations')
        ->assertSet('forceOpen', false)
        ->assertSet('pincode', '560076')
        ->assertSee('Cancel')
        ->assertDontSee('Later');
});

it('shows later only on the first-visit pincode gate', function () {
    Livewire::test(PincodeModal::class)
        ->set('open', true)
        ->set('forceOpen', true)
        ->assertSee('Later')
        ->assertDontSee('Cancel');
});

it('normalizes pincode before saving from the modal', function () {
    PinCode::factory()->create(['pincode' => '560076', 'is_active' => true, 'is_serviceable' => true]);

    Livewire::test(PincodeModal::class)
        ->set('redirectContextPath', '/locations')
        ->set('pincode', '560 076')
        ->call('savePincode')
        ->assertRedirect(url('/locations').'?pin=560076#near-you');
});

it('stores pincode in session and scopes localized services', function () {
    PinCode::factory()->create(['pincode' => '560041', 'is_active' => true]);

    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('location.pincode.store'), ['pincode' => '560041'])
        ->assertRedirect();

    expect($user->fresh()->pincode)->toBe('560041');
    expect(Session::get(config('location.session_key')))->toBe('560041');
});

it('blocks service detail when not available in detected pincode', function () {
    $pinA = PinCode::factory()->create(['pincode' => '560076', 'is_active' => true]);
    $pinB = PinCode::factory()->create(['pincode' => '560041', 'is_active' => true]);
    $service = Service::factory()->create([
        'service_code' => 'local-only-svc',
        'is_active' => true,
        'visibility' => \App\Enums\ServiceVisibility::Public,
    ]);
    $service->pincodes()->attach($pinA->id);

    app(UserLocationService::class)->rememberPincode('560041');

    $this->get(route('public.services.show', $service->service_code))->assertNotFound();
});

it('allows review only after completed lead for service', function () {
    $user = User::factory()->create(['email' => 'patient@example.com']);
    $service = Service::factory()->create(['title' => 'Home Nursing']);

    Lead::query()->create([
        'name' => 'Patient',
        'phone' => '9999999999',
        'email' => 'patient@example.com',
        'service' => $service->title,
        'source' => LeadSource::Organic,
        'status' => LeadStatus::Converted,
    ]);

    $this->actingAs($user);

    Livewire::test(\App\Livewire\Reviews\ReviewForm::class, ['serviceId' => $service->id])
        ->set('rating', 5)
        ->set('comment', 'Excellent care')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('reviews', [
        'user_id' => $user->id,
        'service_id' => $service->id,
        'rating' => 5,
        'status' => 'pending',
    ]);
});
