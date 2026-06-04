<?php

use App\Models\User;

it('shows the growth readiness hub tab', function () {
    $user = User::factory()->create(['role' => 'manager']);

    $this->actingAs($user)
        ->get(route('growth-center.readiness'))
        ->assertSuccessful()
        ->assertSee('Readiness', false)
        ->assertSee('Overall readiness', false)
        ->assertSee('Suggestions', false);
});

it('redirects legacy competitor readiness tab query to canonical readiness url', function () {
    $user = User::factory()->create(['role' => 'manager']);

    $this->actingAs($user)
        ->get(route('growth-center.competitors.index', ['tab' => 'readiness']))
        ->assertRedirect(route('growth-center.readiness'));
});
