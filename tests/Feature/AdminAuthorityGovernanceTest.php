<?php

use App\Enums\AdminLifecycleState;
use App\Models\Block;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\Service;
use App\Services\Blocks\BlockTemplateSyncService;
use App\Services\Governance\AdminAuthorityGuard;
use App\Services\Governance\DownstreamArtifactPurger;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    if (! Schema::hasColumn('blocks', 'lifecycle_state')) {
        $this->markTestSkipped('Admin authority migration not applied.');
    }
});

it('marks blocks deleted by admin and blocks auto-heal restore', function () {
    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    $block = Block::query()->where('block_slug', 'hero-home')->firstOrFail();
    app(AdminAuthorityGuard::class)->markDeletedByAdmin($block);
    $block->delete();

    $result = app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    expect($result['skipped'])->toContain('hero-home')
        ->and(Block::query()->where('block_slug', 'hero-home')->exists())->toBeFalse();
});

it('purges registry orphans when service no longer exists', function () {
    $service = Service::factory()->create(['service_code' => 'orphan-test-service']);

    PageRegistry::query()->create([
        'registry_key' => 'service:orphan-test-service',
        'entity_type' => 'service',
        'entity_id' => $service->id,
        'page_category' => 'service',
        'owner' => 'operations_service',
        'source' => 'generated',
        'public_path' => '/services/orphan-test-service',
        'is_listed' => true,
    ]);

    $service->delete();

    $result = app(DownstreamArtifactPurger::class)->purgeRegistryOrphans();

    expect($result['registry_removed'])->toBeGreaterThanOrEqual(1)
        ->and(PageRegistry::query()->where('registry_key', 'service:orphan-test-service')->exists())->toBeFalse();
});

it('records blocked automated writes in audit log', function () {
    if (! Schema::hasTable('automated_write_audits')) {
        $this->markTestSkipped('automated_write_audits table missing.');
    }

    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    $block = Block::query()->where('block_slug', 'hero-home')->firstOrFail();
    $block->forceFill(['lifecycle_state' => AdminLifecycleState::DeletedByAdmin->value])->saveQuietly();
    $block->delete();

    app(BlockTemplateSyncService::class)->sync(slugs: ['hero-home'], backup: false);

    expect(
        \Illuminate\Support\Facades\DB::table('automated_write_audits')
            ->where('process', 'BlockTemplateSyncService')
            ->where('outcome', 'blocked')
            ->where('record_key', 'hero-home')
            ->exists()
    )->toBeTrue();
});

it('purges page registry when admin deletes page', function () {
    $page = Page::factory()->create(['slug' => 'authority-delete-page']);

    PageRegistry::query()->create([
        'registry_key' => 'page:authority-delete-page',
        'page_id' => $page->id,
        'entity_type' => 'page',
        'entity_id' => $page->id,
        'page_category' => 'other',
        'owner' => 'manual',
        'source' => 'manual',
        'public_path' => '/authority-delete-page',
        'is_listed' => true,
    ]);

    app(AdminAuthorityGuard::class)->markDeletedByAdmin($page);
    app(DownstreamArtifactPurger::class)->purgeForDeletedPage($page);
    $page->delete();

    expect(PageRegistry::query()->where('registry_key', 'page:authority-delete-page')->exists())->toBeFalse();
});
