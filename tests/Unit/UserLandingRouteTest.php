<?php

use App\Models\User;
use App\ModuleAccess;
use App\Support\UserLandingRoute;

it('lands on the first granted module route', function () {
    $user = User::factory()->make([
        'module_access' => [
            ModuleAccess::DASHBOARD => false,
            ModuleAccess::OPERATIONS => true,
            ModuleAccess::MARKETING => true,
        ],
    ]);

    expect(UserLandingRoute::pathFor($user))->toBe(route('modules.operations', absolute: false));
});

it('lands marketing staff on marketing when operations is not granted', function () {
    $user = User::factory()->make([
        'module_access' => [
            ModuleAccess::DASHBOARD => false,
            ModuleAccess::MARKETING => true,
        ],
    ]);

    expect(UserLandingRoute::pathFor($user))->toBe(route('marketing.dashboard', absolute: false));
});

it('lands users with dashboard access on the main dashboard', function () {
    $user = User::factory()->make([
        'module_access' => [
            ModuleAccess::DASHBOARD => true,
            ModuleAccess::MARKETING => true,
        ],
    ]);

    expect(UserLandingRoute::pathFor($user))->toBe(route('dashboard', absolute: false));
});
