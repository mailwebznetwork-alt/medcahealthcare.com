<?php

namespace App\Services\Blocks;

use App\Enums\AdminLifecycleState;
use App\Models\Block;
use App\Services\Governance\AdminAuthorityGuard;
use App\Services\Governance\AutomatedWriteAuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BlockTemplateSyncService
{
    public function __construct(
        private readonly BlockTemplateRegistry $registry,
        private readonly AdminAuthorityGuard $authority,
        private readonly AutomatedWriteAuditLogger $audit,
    ) {}

    /**
     * @param  list<string>|null  $slugs
     * @param  list<string>|null  $categories
     * @return array{synced: list<string>, restored: list<string>, skipped: list<string>, backup: string|null}
     */
    public function sync(?array $slugs = null, ?array $categories = null, bool $backup = true, ?bool $restoreTrashed = null): array
    {
        $restoreTrashed ??= (bool) config('governance.block_sync_restore_trashed', false);
        $definitions = $this->resolveDefinitions($slugs, $categories);

        $backupPath = null;
        if ($backup && $definitions !== []) {
            $backupPath = $this->backupBlocks(array_keys($definitions));
        }

        $synced = [];
        $restored = [];
        $skipped = [];

        foreach ($definitions as $slug => $definition) {
            $existing = Block::withTrashed()->where('block_slug', $slug)->first();

            if (! $this->authority->canRecreateBlockSlug($slug, 'BlockTemplateSyncService')) {
                $skipped[] = $slug;

                continue;
            }

            if ($existing?->trashed()) {
                if ($restoreTrashed && $this->authority->canRestoreBlock($existing, 'BlockTemplateSyncService')) {
                    $existing->restore();
                    $existing->markLifecycle(AdminLifecycleState::SystemManaged)->saveQuietly();
                    $restored[] = $slug;
                } else {
                    if ($existing->isDeletedByAdmin()) {
                        $this->authority->canRestoreBlock($existing, 'BlockTemplateSyncService');
                    }
                    $skipped[] = $slug;

                    continue;
                }
            }

            $code = $this->registry->resolveCode($definition, $slug);

            $block = Block::query()->updateOrCreate(
                ['block_slug' => $slug],
                [
                    'block_name' => (string) ($definition['block_name'] ?? Str::title(str_replace('-', ' ', $slug))),
                    'description' => $definition['description'] ?? null,
                    'block_type' => $definition['block_type'] ?? null,
                    'code' => $code,
                    'is_active' => true,
                    'is_managed' => true,
                    'lifecycle_state' => AdminLifecycleState::SystemManaged->value,
                ]
            );

            $this->audit->log(
                process: 'BlockTemplateSyncService',
                action: 'sync_block',
                table: 'blocks',
                recordId: $block->id,
                recordKey: $slug,
                newValues: ['block_slug' => $slug, 'is_managed' => true],
            );

            $synced[] = $slug;
        }

        return [
            'synced' => $synced,
            'restored' => $restored,
            'skipped' => $skipped,
            'backup' => $backupPath,
        ];
    }

    /**
     * @param  list<string>  $slugs
     */
    public function backupBlocks(array $slugs): string
    {
        $directory = (string) config('block_templates.backup_directory', storage_path('app/block-backups'));
        File::ensureDirectoryExists($directory);

        $timestamp = now()->format('Y-m-d_His');
        $path = $directory.DIRECTORY_SEPARATOR."blocks-{$timestamp}.json";

        $payload = Block::withTrashed()
            ->whereIn('block_slug', $slugs)
            ->get()
            ->map(static fn (Block $block): array => [
                'block_slug' => $block->block_slug,
                'block_name' => $block->block_name,
                'description' => $block->description,
                'block_type' => $block->block_type,
                'code' => $block->code,
                'custom_css' => $block->custom_css,
                'schema_json' => $block->schema_json,
                'is_active' => $block->is_active,
                'is_managed' => $block->is_managed,
                'deleted_at' => $block->deleted_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        File::put($path, json_encode([
            'created_at' => now()->toIso8601String(),
            'slugs' => $slugs,
            'blocks' => $payload,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * @return array{restored: list<string>}
     */
    public function restoreFromBackup(string $path): array
    {
        if (! File::exists($path)) {
            throw new \RuntimeException("Block backup file not found: {$path}");
        }

        $decoded = json_decode(File::get($path), true);
        if (! is_array($decoded) || ! is_array($decoded['blocks'] ?? null)) {
            throw new \RuntimeException('Invalid block backup JSON.');
        }

        $restored = [];

        foreach ($decoded['blocks'] as $row) {
            if (! is_array($row) || ! isset($row['block_slug'])) {
                continue;
            }

            $slug = (string) $row['block_slug'];

            if (! $this->authority->canRecreateBlockSlug($slug, 'BlockTemplateSyncService::restoreFromBackup')) {
                continue;
            }

            $block = Block::withTrashed()->firstOrNew(['block_slug' => $slug]);

            if ($block->trashed() && ! $this->authority->canRestoreBlock($block, 'BlockTemplateSyncService::restoreFromBackup')) {
                continue;
            }

            if ($block->trashed()) {
                $block->restore();
            }

            $block->fill([
                'block_name' => $row['block_name'] ?? $block->block_name,
                'description' => $row['description'] ?? null,
                'block_type' => $row['block_type'] ?? null,
                'code' => $row['code'] ?? '',
                'custom_css' => $row['custom_css'] ?? null,
                'schema_json' => $row['schema_json'] ?? null,
                'is_active' => (bool) ($row['is_active'] ?? true),
                'is_managed' => (bool) ($row['is_managed'] ?? false),
            ]);
            $block->save();

            $restored[] = $slug;
        }

        return ['restored' => $restored];
    }

    /**
     * @return list<string>
     */
    public function listBackups(): array
    {
        $directory = (string) config('block_templates.backup_directory', storage_path('app/block-backups'));
        if (! File::isDirectory($directory)) {
            return [];
        }

        return Collection::make(File::files($directory))
            ->sortByDesc(static fn (\SplFileInfo $file): int => $file->getMTime())
            ->map(static fn (\SplFileInfo $file): string => $file->getPathname())
            ->values()
            ->all();
    }

    /**
     * @param  list<string>|null  $slugs
     * @param  list<string>|null  $categories
     * @return array<string, array<string, mixed>>
     */
    private function resolveDefinitions(?array $slugs, ?array $categories): array
    {
        if ($slugs !== null && $slugs !== []) {
            return $this->registry->forSlugs($slugs);
        }

        if ($categories !== null && $categories !== []) {
            return $this->registry->forCategories($categories);
        }

        return $this->registry->all();
    }
}
