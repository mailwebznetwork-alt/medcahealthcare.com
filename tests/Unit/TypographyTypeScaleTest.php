<?php

use App\Support\TypographyTypeScale;
use Illuminate\Validation\ValidationException;

it('defaults does not recurse into normalize', function () {
    $start = memory_get_usage();
    $defaults = TypographyTypeScale::defaults();

    expect($defaults)->not->toBeEmpty()
        ->and(memory_get_usage() - $start)->toBeLessThan(5_000_000);
});

it('normalizes and validates custom type scale', function () {
    $custom = TypographyTypeScale::normalize([
        'h1' => [
            'desktop' => ['size' => 3, 'weight' => 700, 'line_height' => 1.1],
        ],
    ]);

    expect($custom['h1']['desktop']['size'])->toBe(3.0);

    TypographyTypeScale::assertValid($custom);
});

it('rejects invalid rem size', function () {
    TypographyTypeScale::assertValid([
        'h1' => [
            'desktop' => ['size' => 10, 'weight' => 700, 'line_height' => 1.2],
        ],
    ]);
})->throws(ValidationException::class);
