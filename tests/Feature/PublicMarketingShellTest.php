<?php

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Support\Facades\Route;

it('renders the public marketing shell with Medca chrome', function () {
    $this->get('/')->assertSuccessful()
        ->assertSee(config('medca.top_bar_claim'), false)
        ->assertSee(config('medca.brand_name'), false)
        ->assertSee('medca-logo.png', false)
        ->assertSee('medca-public-surface', false);
});

it('shows staff login on the public footer for guests when login route exists', function () {
    if (! Route::has('login')) {
        $this->markTestSkipped('Login route is not registered.');
    }

    $this->get('/')->assertSuccessful()
        ->assertSee('Staff login', false);
});

it('shows workspace entry points for signed-in staff on the home page', function () {
    if (! Route::has('growth-center.readiness')) {
        $this->markTestSkipped('Growth readiness route is not registered.');
    }

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $key): array => [$key => true])
            ->all(),
        'role' => 'admin',
    ]);

    $this->actingAs($admin)->get('/')->assertSuccessful()
        ->assertSee('Open dashboard', false)
        ->assertSee('SEO readiness hub', false);
});
