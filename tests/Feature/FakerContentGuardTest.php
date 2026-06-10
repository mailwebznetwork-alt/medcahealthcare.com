<?php

use App\Models\ServiceCategory;
use App\Rules\RejectFakerContent;
use App\Support\FakerContentGuard;

it('detects latin faker catalog names', function () {
    $guard = app(FakerContentGuard::class);

    expect($guard->isFakerLike('Dolor Aut'))->toBeTrue()
        ->and($guard->isFakerLike('Nihil Facilis'))->toBeTrue()
        ->and($guard->isFakerLike('Caregiver Services'))->toBeFalse()
        ->and($guard->isFakerLike('cat-caregiver'))->toBeFalse();
});

it('rejects faker content in production via validation rule', function () {
    config(['app.env' => 'production']);
    app()->instance('env', 'production');

    $rule = new RejectFakerContent;
    $failed = false;
    $rule->validate('name', 'Dolor Aut', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('purges faker categories with the cleanup command', function () {
    ServiceCategory::factory()->create([
        'name' => 'Dolor Aut',
        'code' => 'dolor-aut',
    ]);

    $this->artisan('medca:purge-faker-data')
        ->assertSuccessful();

    expect(ServiceCategory::query()->where('code', 'dolor-aut')->exists())->toBeFalse();
});
