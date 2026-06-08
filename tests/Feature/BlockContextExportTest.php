<?php

use App\Livewire\SiteArchitect\BlockFactory;
use App\Models\Block;
use App\Models\Page;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Blocks\BlockContextExporter;
use App\Services\Blocks\BlockTemplateSyncService;
use Livewire\Livewire;

it('exports full block context for managed blocks including blade source and usage', function () {
    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    Page::query()->updateOrCreate(
        ['slug' => 'home'],
        ['title' => 'Home', 'content' => '{{block:hero-home}}', 'is_active' => true]
    );

    $block = Block::query()->where('block_slug', 'hero-home')->firstOrFail();
    $export = app(BlockContextExporter::class)->export($block);

    expect($export)
        ->toContain('# Medca Block Context Export')
        ->toContain('**Name:** Home — Hero')
        ->toContain('**Slug:** `hero-home`')
        ->toContain('## Purpose')
        ->toContain('Marketing hero for the public home page')
        ->toContain('## Content (resolved marketing copy)')
        ->toContain('**headline:**')
        ->toContain('## HTML / Blade source')
        ->toContain('<x-public.hero')
        ->toContain('## Schema')
        ->toContain('**eyebrow**')
        ->toContain('## Usage locations')
        ->toContain('**page** `home`')
        ->toContain('## Structured JSON (Gemini / tooling)')
        ->toContain('"export_type": "medca_block_context"');
});

it('exports inline code and custom css for custom blocks', function () {
    $block = Block::factory()->create([
        'block_slug' => 'custom-export-block',
        'block_name' => 'Custom Export Block',
        'description' => 'Test purpose for export',
        'code' => '<div class="custom">{{ $headline }}</div>',
        'custom_css' => '.custom { color: red; }',
        'settings_json' => ['content' => ['headline' => 'Hello World']],
        'is_managed' => false,
    ]);

    $payload = app(BlockContextExporter::class)->toArray($block);

    expect($payload['purpose'])->toBe('Test purpose for export')
        ->and($payload['html_blade'])->toContain('<div class="custom">')
        ->and($payload['css'])->toBe('.custom { color: red; }')
        ->and($payload['settings'])->toBe(['content' => ['headline' => 'Hello World']]);
});

it('copies block context to clipboard via livewire dispatch', function () {
    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    $user = User::factory()->create([
        'role' => 'super_admin',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(static fn (string $key): array => [$key => true])
            ->all(),
    ]);

    $block = Block::query()->where('block_slug', 'hero-home')->firstOrFail();

    Livewire::actingAs($user)
        ->test(BlockFactory::class)
        ->call('copyBlockContext', $block->id)
        ->assertDispatched('block-context-copied');
});
