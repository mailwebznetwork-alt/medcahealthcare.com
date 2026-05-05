<?php

use App\Models\User;

it('shows the growth readiness hub tab', function () {
    $user = User::factory()->create(['role' => 'manager']);

    $this->actingAs($user)
        ->get(route('growth-center.competitors.index', ['tab' => 'readiness']))
        ->assertSuccessful()
        ->assertSee('READINESS', false)
        ->assertSee('Overall readiness', false)
        ->assertSee('Suggestions', false);
});

it('redirects the readiness shortcut route to the readiness tab', function () {
    $user = User::factory()->create(['role' => 'manager']);

    $this->actingAs($user)
        ->get(route('growth-center.readiness'))
        ->assertRedirect(route('growth-center.competitors.index', ['tab' => 'readiness']));
});
