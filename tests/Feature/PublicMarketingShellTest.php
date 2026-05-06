<?php

use App\Models\User;
use App\ModuleAccess;

it('renders the public marketing shell with Medca chrome', function () {
    $this->get('/')->assertSuccessful()
        ->assertSee(config('medca.brand_name'), false)
        ->assertSee(config('medca.tagline'), false)
        ->assertSee('medca-logo.png', false)
        ->assertSee('medca-public-surface', false)
        ->assertSee('scroll-mt-32', false);
});

it('documents approximate sticky marketing header height for anchor offset math', function () {
    expect(config('medca.marketing_sticky_header_approx_px'))->toBeInt()->toBeGreaterThan(0);
});

it('shows compact centered footer line', function () {
    $this->get('/')->assertSuccessful()
        ->assertSee('Powered by MarkOnMinds.', false)
        ->assertDontSee('Staff login', false);
});

it('keeps public shell unchanged for signed-in staff', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $key): array => [$key => true])
            ->all(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)->get('/')->assertSuccessful()
        ->assertDontSee('Open dashboard', false)
        ->assertDontSee('SEO readiness hub', false);
});
