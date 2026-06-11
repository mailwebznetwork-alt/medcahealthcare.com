<?php

namespace App\Services\Governance;

use App\Enums\AdminLifecycleState;
use App\Models\AdminDeletionTombstone;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Support\ServicePageOverrides;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class SourceOfTruthListService
{
    /** @var list<string> */
    public const METRICS = [
        'registry_rows',
        'pages',
        'synced_pages',
        'generated',
        'manual',
        'planned',
        'orphan_registry',
        'tombstones',
        'protected_pages',
        'admin_overrides',
    ];

    public function __construct(
        private readonly DownstreamArtifactPurger $purger,
    ) {}

    public static function supports(string $key): bool
    {
        return in_array($key, self::METRICS, true);
    }

    public function label(string $key): string
    {
        return match ($key) {
            'registry_rows' => __('Registry rows'),
            'pages' => __('Pages'),
            'synced_pages' => __('Synced pages'),
            'generated' => __('Generated'),
            'manual' => __('Manual'),
            'planned' => __('Planned'),
            'orphan_registry' => __('Orphan registry'),
            'tombstones' => __('Tombstones'),
            'protected_pages' => __('Protected pages'),
            'admin_overrides' => __('Admin overrides'),
            default => __('Records'),
        };
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function columns(string $key): array
    {
        return match ($key) {
            'registry_rows', 'synced_pages', 'generated', 'manual', 'planned', 'orphan_registry' => [
                ['key' => 'registry_key', 'label' => __('Registry key')],
                ['key' => 'entity_type', 'label' => __('Type')],
                ['key' => 'source', 'label' => __('Source')],
                ['key' => 'page', 'label' => __('Page')],
                ['key' => 'public_path', 'label' => __('Path')],
            ],
            'pages', 'protected_pages', 'admin_overrides' => [
                ['key' => 'title', 'label' => __('Title')],
                ['key' => 'slug', 'label' => __('Slug')],
                ['key' => 'lifecycle_state', 'label' => __('Lifecycle')],
                ['key' => 'page_source', 'label' => __('Source')],
                ['key' => 'open', 'label' => __('Open')],
            ],
            'tombstones' => [
                ['key' => 'entity_type', 'label' => __('Entity type')],
                ['key' => 'natural_key', 'label' => __('Natural key')],
                ['key' => 'deleted_at', 'label' => __('Deleted at')],
                ['key' => 'source', 'label' => __('Source')],
                ['key' => 'reason', 'label' => __('Reason')],
            ],
            default => [],
        };
    }

    public function paginate(string $key, int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->query($key);

        if ($query === null) {
            return Page::query()->whereRaw('0 = 1')->paginate($perPage);
        }

        return $query->paginate($perPage);
    }

    public function query(string $key): ?Builder
    {
        return match ($key) {
            'registry_rows' => PageRegistry::query()->with('page')->orderByDesc('id'),
            'pages' => Page::query()->orderByDesc('id'),
            'synced_pages' => PageRegistry::query()->whereNotNull('page_id')->with('page')->orderByDesc('id'),
            'generated' => PageRegistry::query()->where('source', 'generated')->with('page')->orderByDesc('id'),
            'manual' => PageRegistry::query()->where('source', 'manual')->with('page')->orderByDesc('id'),
            'planned' => PageRegistry::query()->where('source', 'planned')->with('page')->orderByDesc('id'),
            'orphan_registry' => $this->orphanRegistryQuery(),
            'tombstones' => Schema::hasTable('admin_deletion_tombstones')
                ? AdminDeletionTombstone::query()->orderByDesc('deleted_at')
                : null,
            'protected_pages' => Page::query()
                ->whereIn('lifecycle_state', [
                    AdminLifecycleState::Disabled->value,
                    AdminLifecycleState::Archived->value,
                    AdminLifecycleState::DeletedByAdmin->value,
                ])
                ->orderByDesc('id'),
            'admin_overrides' => ServicePageOverrides::adminAuthorityQuery(),
            default => null,
        };
    }

    private function orphanRegistryQuery(): Builder
    {
        $keys = collect($this->purger->previewRegistryOrphans())
            ->pluck('registry_key')
            ->filter()
            ->values()
            ->all();

        return PageRegistry::query()
            ->with('page')
            ->when(
                $keys === [],
                fn (Builder $query) => $query->whereRaw('0 = 1'),
                fn (Builder $query) => $query->whereIn('registry_key', $keys),
            )
            ->orderBy('registry_key');
    }
}
