<?php

use App\Models\User;
use App\ModuleAccess;

it('allows marketing users to open intelligence dashboard', function () {
    $user = User::factory()->create([
        'role' => 'manager',
        'module_access' => ModuleAccess::defaultGrants(),
    ]);

    $this->actingAs($user)
        ->get(route('marketing.intelligence'))
        ->assertSuccessful()
        ->assertSee(__('Marketing Intelligence'));
});

it('blocks users without marketing module', function () {
    $access = ModuleAccess::defaultGrants();
    $access['marketing'] = false;

    $user = User::factory()->create([
        'role' => 'manager',
        'module_access' => $access,
    ]);

    $this->actingAs($user)
        ->get(route('marketing.intelligence'))
        ->assertForbidden();
});
