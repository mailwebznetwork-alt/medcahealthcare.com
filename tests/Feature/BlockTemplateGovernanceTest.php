<?php

use App\Livewire\SiteArchitect\BlockFactory;
use App\Models\Block;
use App\Models\Page;
use App\Models\User;
use App\ModuleAccess;
use App\Services\Blocks\BlockTemplateSyncService;
use App\Support\BlockContent;
use Database\Seeders\MedcaCareersPageSeeder;
use Database\Seeders\MedcaPublicPagesSeeder;
use Livewire\Livewire;

it('registers managed block templates including shared element library', function () {
    $templates = config('block_templates.templates');
    expect($templates)->toHaveCount(73)
        ->and($templates)->toHaveKey('hero-centered')
        ->and($templates)->toHaveKey('faq-accordion')
        ->and($templates)->toHaveKey('lead-magnet-guide');
});

it('syncs Git templates into the database with include-based code', function () {
    $result = app(BlockTemplateSyncService::class)->sync(
        slugs: ['hero-home'],
        backup: false,
    );

    expect($result['synced'])->toContain('hero-home');

    $block = Block::query()->where('block_slug', 'hero-home')->first();

    expect($block)->not->toBeNull()
        ->and($block->is_managed)->toBeTrue()
        ->and($block->code)->toBe("@include('blocks.home.hero-home')");
});

it('renders synced managed blocks on the public home page', function () {
    app(BlockTemplateSyncService::class)->sync(categories: ['home'], backup: false);

    Page::query()->updateOrCreate(
        ['slug' => 'home'],
        [
            'title' => 'Home',
            'content' => '{{block:hero-home}}',
            'is_active' => true,
        ]
    );

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Premium home healthcare', false);
});

it('restores soft-deleted managed blocks when syncing', function () {
    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    $block = Block::query()->where('block_slug', 'hero-home')->firstOrFail();
    $block->delete();

    expect(Block::query()->where('block_slug', 'hero-home')->exists())->toBeFalse();

    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    expect(Block::query()->where('block_slug', 'hero-home')->exists())->toBeTrue();
});

it('filters block factory listing by search', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(static fn (string $key): array => [$key => true])
            ->all(),
    ]);

    Block::factory()->create([
        'block_name' => 'Alpha Hero Banner',
        'block_slug' => 'alpha-hero-banner',
        'block_type' => 'Hero',
    ]);
    Block::factory()->create([
        'block_name' => 'Beta FAQ List',
        'block_slug' => 'beta-faq-list',
        'block_type' => 'Text',
    ]);

    Livewire::actingAs($user)
        ->test(BlockFactory::class)
        ->set('search', 'alpha')
        ->assertSee('Alpha Hero Banner')
        ->assertDontSee('Beta FAQ List');
});

it('allows renaming duplicated managed block copies in block factory', function () {
    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    $user = User::factory()->create([
        'role' => 'super_admin',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(static fn (string $key): array => [$key => true])
            ->all(),
    ]);

    $managed = Block::query()->where('block_slug', 'hero-home')->firstOrFail();

    Livewire::actingAs($user)
        ->test(BlockFactory::class)
        ->call('duplicateBlock', $managed->id)
        ->call('startEdit', Block::query()->where('block_slug', 'hero-home-copy')->value('id'))
        ->set('block_name', 'Renamed Copy Hero')
        ->set('block_slug', 'renamed-copy-hero')
        ->call('saveBlock')
        ->assertHasNoErrors();

    expect(Block::query()->where('block_slug', 'renamed-copy-hero')->value('block_name'))
        ->toBe('Renamed Copy Hero');
});

it('prevents removing managed blocks for non bypass operators', function () {
    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    $user = User::factory()->create([
        'role' => 'super_admin',
        'name' => 'Editor User',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(static fn (string $key): array => [$key => true])
            ->all(),
    ]);
    $block = Block::query()->where('block_slug', 'hero-home')->firstOrFail();

    Livewire::actingAs($user)
        ->test(BlockFactory::class)
        ->call('removeBlock', $block->id)
        ->assertHasNoErrors();

    expect(Block::query()->where('block_slug', 'hero-home')->exists())->toBeTrue();
});

it('allows WDJERRIE to remove managed blocks from block factory', function () {
    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    $user = User::factory()->create([
        'role' => 'super_admin',
        'name' => 'WDJERRIE',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(static fn (string $key): array => [$key => true])
            ->all(),
    ]);
    $block = Block::query()->where('block_slug', 'hero-home')->firstOrFail();

    Livewire::actingAs($user)
        ->test(BlockFactory::class)
        ->call('removeBlock', $block->id)
        ->assertHasNoErrors();

    expect(Block::query()->where('block_slug', 'hero-home')->exists())->toBeFalse();
});

it('removes custom blocks from block factory', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(static fn (string $key): array => [$key => true])
            ->all(),
    ]);

    $block = Block::factory()->create(['block_slug' => 'removable-test-block']);

    Livewire::actingAs($user)
        ->test(BlockFactory::class)
        ->call('removeBlock', $block->id)
        ->assertHasNoErrors();

    expect(Block::query()->where('block_slug', 'removable-test-block')->exists())->toBeFalse();
});

it('resolves marketing schema slug from a blocks include in code', function () {
    expect(BlockContent::resolveSchemaSlug('herox-services', "@include('blocks.careers.hero-careers')"))
        ->toBe('hero-careers');
    expect(BlockContent::marketingCopyVisible('herox-services', "@include('blocks.careers.hero-careers')"))
        ->toBeTrue();
});

it('shows eyebrow marketing fields in block factory when editing hero-home', function () {
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
        ->call('startEdit', $block->id)
        ->assertSee('Eyebrow')
        ->assertSee('Marketing copy');
});

it('persists eyebrow from block factory save', function () {
    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-services'], backup: false);

    $user = User::factory()->create([
        'role' => 'super_admin',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(static fn (string $key): array => [$key => true])
            ->all(),
    ]);

    $block = Block::query()->where('block_slug', 'hero-services')->firstOrFail();

    Livewire::actingAs($user)
        ->test(BlockFactory::class)
        ->call('startEdit', $block->id)
        ->set('block_content.eyebrow', 'MEDCA HEALTH CARE')
        ->call('saveBlock')
        ->assertHasNoErrors();

    $block->refresh();
    expect($block->settings_json['content']['eyebrow'] ?? null)->toBe('MEDCA HEALTH CARE');
});

it('writes a JSON backup before syncing when backup is enabled', function () {
    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: true);

    $backups = app(BlockTemplateSyncService::class)->listBackups();

    expect($backups)->not->toBeEmpty();
    expect(file_exists($backups[0]))->toBeTrue();
});

it('seeds marketing pages through the template sync service', function () {
    $this->seed(MedcaPublicPagesSeeder::class);

    expect(Block::query()->where('block_slug', 'hero-home')->value('is_managed'))->toBeTrue();
    expect(Block::query()->where('block_slug', 'hero-home')->value('code'))->toBe("@include('blocks.home.hero-home')");
});

it('seeds careers blocks as managed templates', function () {
    $this->seed(MedcaCareersPageSeeder::class);

    expect(Block::query()->where('block_slug', 'careers-open-roles')->value('is_managed'))->toBeTrue();
    expect(Block::query()->where('block_slug', 'careers-open-roles')->value('code'))
        ->toBe("@include('blocks.careers.open-roles-listing', ['vacancies' => \$vacancies ?? collect()])");
});

it('can restore blocks from a JSON backup file', function () {
    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    $block = Block::query()->where('block_slug', 'hero-home')->firstOrFail();
    $block->update(['code' => '<section>Custom backup marker</section>']);

    $path = app(BlockTemplateSyncService::class)->backupBlocks(['hero-home']);

    $block->update(['code' => '<section>Changed after backup</section>']);

    app(BlockTemplateSyncService::class)->restoreFromBackup($path);

    expect(Block::query()->where('block_slug', 'hero-home')->value('code'))
        ->toBe('<section>Custom backup marker</section>');
});
