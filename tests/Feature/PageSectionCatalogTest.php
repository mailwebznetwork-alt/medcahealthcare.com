<?php

use App\Models\Block;
use App\Services\SiteArchitect\PageSectionCatalog;

it('resolves friendly display names without exposing slug jargon', function () {
    Block::query()->create([
        'block_slug' => 'hero-healthcare',
        'block_name' => 'Element — Hero Healthcare',
        'block_type' => 'Hero',
        'description' => 'Premium healthcare hero.',
        'code' => '<p></p>',
        'is_active' => true,
        'is_managed' => true,
    ]);

    $name = app(PageSectionCatalog::class)->displayNameForSlug('hero-healthcare');

    expect($name)->toBe('Healthcare Hero')
        ->and($name)->not->toContain('hero-healthcare');
});

it('groups picker entries by category', function () {
    Block::query()->create([
        'block_slug' => 'faq-accordion',
        'block_name' => 'Element — Faq Accordion',
        'block_type' => 'FAQ',
        'code' => '<p></p>',
        'is_active' => true,
    ]);

    $grouped = app(PageSectionCatalog::class)->groupedForPicker();

    expect($grouped)->toHaveKey('FAQ');
});
