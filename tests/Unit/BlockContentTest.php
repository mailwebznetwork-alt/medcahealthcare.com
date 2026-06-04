<?php

use App\Support\BlockContent;

it('returns stored block content when set', function () {
    $settings = ['content' => ['headline' => 'Custom headline']];

    expect(BlockContent::get($settings, 'hero-home', 'headline'))
        ->toBe('Custom headline');
});

it('falls back to schema default when content empty', function () {
    expect(BlockContent::get([], 'hero-home', 'eyebrow'))
        ->toBe('Premium Home Healthcare · Bangalore');
});

it('reports schema presence', function () {
    expect(BlockContent::hasSchema('hero-home'))->toBeTrue()
        ->and(BlockContent::hasSchema('nonexistent-block'))->toBeFalse();
});
