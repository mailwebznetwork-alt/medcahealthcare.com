<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('shows ai pulse tab in growth center for authorized users', function () {
    if (! Schema::hasTable('competitors')) {
        $this->markTestSkipped('Competitors table is not migrated.');
    }

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => ['growth_center' => true],
    ]);

    $this->actingAs($user)
        ->get(route('growth-center.competitors.index', ['tab' => 'ai-pulse']))
        ->assertOk()
        ->assertSeeLivewire('growth.ai-pulse-panel');
});
