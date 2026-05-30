<?php

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\User;
use App\Services\UserLocationService;
use Illuminate\Support\Facades\Session;

it('stores pincode in session and scopes localized services', function () {
    $pin = PinCode::factory()->create(['pincode' => '560076', 'is_active' => true, 'is_serviceable' => true]);
    $service = Service::factory()->create();
    $service->pincodes()->attach($pin->id);

    Service::factory()->create();

    $location = app(UserLocationService::class);
    expect($location->setManualPincode('560076'))->toBe('560076');

    $scoped = Service::query()->localizedListing('560076')->pluck('id');
    expect($scoped)->toContain($service->id)->toHaveCount(1);
});

it('persists pincode on authenticated user profile', function () {
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

    Livewire\Livewire::test(\App\Livewire\Reviews\ReviewForm::class, ['serviceId' => $service->id])
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
