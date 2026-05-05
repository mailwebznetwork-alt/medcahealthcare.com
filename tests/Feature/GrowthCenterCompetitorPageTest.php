<?php

use App\Models\Competitor;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('shows growth center competitors page for authorized users', function () {
    if (! Schema::hasTable('competitors')) {
        $this->markTestSkipped('Competitors table is not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    Competitor::query()->create([
        'name' => 'Demo Competitor',
        'website' => 'https://demo.example.com',
        'is_active' => true,
        'is_intercept_target' => true,
    ]);

    $this->actingAs($user)
        ->get(route('growth-center.competitors.index'))
        ->assertOk()
        ->assertSeeText('Competitors')
        ->assertSeeText('Demo Competitor');
});
