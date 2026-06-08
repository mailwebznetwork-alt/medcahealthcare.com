<?php

namespace App\Services\Bulk;

use App\Models\Block;
use App\Models\Blog;
use App\Models\Page;
use App\Models\SectionLibraryItem;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Blocks\BlockTemplateSyncService;
use App\Services\Governance\AdminAuthorityGuard;
use App\Services\Governance\DownstreamArtifactPurger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkActionService
{
    public function __construct(
        private readonly BulkGovernancePreview $preview,
        private readonly ActivityLogService $activityLog,
        private readonly AdminAuthorityGuard $authorityGuard,
        private readonly DownstreamArtifactPurger $purger,
    ) {}

    /**
     * @param  list<int>  $ids
     * @return array<string, mixed>
     */
    public function governancePreview(string $resourceKey, array $ids, string $action): array
    {
        return match ($resourceKey) {
            'site_architect.pages' => $this->preview->forPages($ids, $action),
            'site_architect.blocks' => $this->preview->forBlocks($ids, $action),
            default => [
                'selected_count' => count($ids),
                'action' => $action,
                'affected_pages' => [],
                'affected_registry_rows' => [],
                'affected_mappings' => 0,
                'affected_urls' => [],
                'affected_location_pages' => [],
                'affected_service_pages' => [],
                'cascading_deletions' => [__('Bulk action will apply to :count selected row(s).', ['count' => count($ids)])],
                'requires_delete_confirmation' => $action === 'delete',
            ],
        };
    }

    /**
     * @param  list<int>  $ids
     * @return array{processed: int, skipped: int, message: string}
     */
    public function execute(string $resourceKey, array $ids, string $action, User $user): array
    {
        $config = config("bulk_actions.resources.{$resourceKey}");
        if (! is_array($config)) {
            return ['processed' => 0, 'skipped' => count($ids), 'message' => __('Unknown bulk resource.')];
        }

        $allowed = $config['actions'] ?? [];
        if (! in_array($action, $allowed, true)) {
            return ['processed' => 0, 'skipped' => count($ids), 'message' => __('Action not allowed for this resource.')];
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];
        $processed = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $model = $modelClass::query()->find($id);
            if ($model === null) {
                $skipped++;

                continue;
            }

            $ok = match ($resourceKey) {
                'site_architect.pages' => $this->executePageAction($model, $action, $user),
                'site_architect.blogs' => $this->executeBlogAction($model, $action, $user),
                'site_architect.blocks' => $this->executeBlockAction($model, $action, $user),
                'site_architect.sections' => $this->executeSectionAction($model, $action, $user),
                default => false,
            };

            if ($ok) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        $this->activityLog->log(
            'bulk_'.$action,
            'site_architect',
            strtoupper($resourceKey).' bulk '.$action.': processed='.$processed.', skipped='.$skipped.' by user '.$user->id,
        );

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'message' => __('Bulk :action complete. :processed processed, :skipped skipped.', [
                'action' => $action,
                'processed' => $processed,
                'skipped' => $skipped,
            ]),
        ];
    }

    /**
     * @param  list<int>  $ids
     */
    public function export(string $resourceKey, array $ids, string $format = 'json'): StreamedResponse
    {
        $config = config("bulk_actions.resources.{$resourceKey}");
        /** @var class-string<Model> $modelClass */
        $modelClass = is_array($config) ? $config['model'] : Model::class;
        $rows = $modelClass::query()->whereIn('id', $ids)->get();

        if ($format === 'csv') {
            return Response::streamDownload(function () use ($rows): void {
                $out = fopen('php://output', 'w');
                if ($out === false) {
                    return;
                }
                fputcsv($out, ['id', 'payload']);
                foreach ($rows as $row) {
                    fputcsv($out, [$row->getKey(), json_encode($row->toArray())]);
                }
                fclose($out);
            }, 'bulk-export-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
        }

        return Response::streamDownload(
            fn () => print ($rows->toJson(JSON_PRETTY_PRINT)),
            'bulk-export-'.now()->format('Y-m-d-His').'.json',
            ['Content-Type' => 'application/json'],
        );
    }

    private function executePageAction(Page $page, string $action, User $user): bool
    {
        if (! $user->can('update', $page) && $action !== 'delete') {
            return false;
        }

        return match ($action) {
            'delete' => $this->deletePage($page, $user),
            'publish' => (bool) $page->forceFill(['is_active' => true])->save(),
            'unpublish' => (bool) $page->forceFill(['is_active' => false])->save(),
            'export' => true,
            default => false,
        };
    }

    private function deletePage(Page $page, User $user): bool
    {
        if (! $user->can('delete', $page)) {
            return false;
        }

        $this->authorityGuard->markDeletedByAdmin($page);
        $this->purger->purgeForDeletedPage($page);
        $page->delete();
        $this->activityLog->log('page_delete', 'site_architect', 'Bulk delete page ID '.$page->id);

        return true;
    }

    private function executeBlogAction(Blog $blog, string $action, User $user): bool
    {
        return match ($action) {
            'delete' => (bool) tap($blog, fn (Blog $b) => $b->delete()),
            'publish' => (bool) $blog->forceFill(['is_published' => true, 'published_at' => $blog->published_at ?? now()])->save(),
            'unpublish' => (bool) $blog->forceFill(['is_published' => false])->save(),
            'export' => true,
            default => false,
        };
    }

    private function executeBlockAction(Block $block, string $action, User $user): bool
    {
        return match ($action) {
            'delete' => $this->deleteBlock($block),
            'publish' => (bool) $block->forceFill(['is_active' => true])->save(),
            'unpublish' => (bool) $block->forceFill(['is_active' => false])->save(),
            'sync' => $this->syncBlock($block),
            'export' => true,
            default => false,
        };
    }

    private function deleteBlock(Block $block): bool
    {
        if ($block->is_managed) {
            return false;
        }

        $this->authorityGuard->markDeletedByAdmin($block);
        $block->delete();

        return true;
    }

    private function syncBlock(Block $block): bool
    {
        $result = app(BlockTemplateSyncService::class)->sync(slugs: [(string) $block->block_slug], backup: true);

        return in_array($block->block_slug, $result['synced'] ?? [], true);
    }

    private function executeSectionAction(SectionLibraryItem $section, string $action, User $user): bool
    {
        return match ($action) {
            'delete' => (bool) tap($section, fn (SectionLibraryItem $s) => $s->delete()),
            'export' => true,
            default => false,
        };
    }
}
