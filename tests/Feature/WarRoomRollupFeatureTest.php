<?php

use App\Models\Competitor;
use App\Models\User;
use App\Support\WarRoomRollup;

it('exposes war room rollup metrics on the war-room tab', function () {
    Competitor::query()->create([
        'name' => 'War Room Rollup Corp '.uniqid(),
        'website' => 'https://example.test',
        'is_active' => true,
        'is_intercept_target' => true,
    ]);

    $user = User::factory()->create(['role' => 'manager']);

    $this->actingAs($user)
        ->get(route('growth-center.competitors.index', ['tab' => 'war-room']))
        ->assertSuccessful()
        ->assertSee('Silent Hijack Monitor', false)
        ->assertSee('Keyword intelligence', false)
        ->assertSee('GEO footprint', false);
});

it('invalidates cached rollup after forget()', function () {
    WarRoomRollup::forget();
    $beforeTotal = WarRoomRollup::cached()['total'];

    Competitor::query()->create([
        'name' => 'Cache Invalidation Ltd '.uniqid(),
        'is_active' => true,
        'is_intercept_target' => false,
    ]);

    WarRoomRollup::forget();

    expect(WarRoomRollup::cached()['total'])->toBe($beforeTotal + 1);
});
