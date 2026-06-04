<?php

use App\Models\Block;
use App\Services\Blocks\BlockTemplateSyncService;
use App\Services\ContentParser;
use App\Services\Deployment\BlockSettingsResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('covers all twenty-five element categories in config', function () {
    $required = [
        'hero-centered', 'cta-simple', 'features-grid', 'services-benefits',
        'statistics-row', 'process-steps', 'testimonials-grid', 'reviews-grid',
        'faq-accordion', 'team-grid', 'gallery-grid', 'video-embed', 'contact-split',
        'form-callback', 'pricing-tiers', 'comparison-features', 'trust-bar-icons',
        'logos-partners', 'location-radius', 'before-after', 'timeline-milestones',
        'cards-icon-row', 'content-prose', 'callout-tip', 'lead-magnet-guide',
    ];

    $templates = config('block_templates.templates');

    foreach ($required as $slug) {
        expect($templates)->toHaveKey($slug)
            ->and($templates[$slug]['category'])->toBe('shared');
    }
});

it('syncs and renders a shared element with style pack variant', function () {
    app(BlockTemplateSyncService::class)->sync(slugs: ['faq-accordion'], backup: false);

    $block = Block::query()->where('block_slug', 'faq-accordion')->first();
    expect($block)->not->toBeNull()
        ->and($block->is_managed)->toBeTrue();

    $vars = app(BlockSettingsResolver::class)->renderVariables(
        'faq-accordion',
        null,
        null,
        'healthcare_premium',
    );

    expect($vars['blockStyleClass'])->toContain('medca-block--style-');

    $html = ContentParser::parse('{{block:faq-accordion}}');
    expect($html)->toContain('FAQ')
        ->and($html)->toContain('medca-block');
});

it('seeds builtin sections that reference shared elements', function () {
    $sections = config('section_library_builtin');
    expect($sections)->toHaveKey('landing_healthcare_full')
        ->and($sections['landing_healthcare_full']['blocks_json'][0]['slug'])->toBe('hero-healthcare');
});
