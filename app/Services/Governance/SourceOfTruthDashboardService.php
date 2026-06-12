<?php

namespace App\Services\Governance;

use App\Enums\AdminLifecycleState;
use App\Models\AdminDeletionTombstone;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\ServiceLocationPage;
use App\Support\ServicePageOverrides;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SourceOfTruthDashboardService
{
    public function __construct(
        private readonly DownstreamArtifactPurger $purger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $orphans = $this->purger->previewRegistryOrphans();
        $health = $this->healthAnalysis($orphans);

        return [
            'metrics' => $this->metrics($orphans),
            'last_sync' => $this->lastSyncStatus(),
            'governance' => $this->governanceStatus(),
            'registry' => $this->registryStatus(),
            'cascade' => $this->cascadeStatus(),
            'health' => $health,
            'orphan_rows' => array_slice($orphans, 0, 50),
        ];
    }

    /**
     * @param  list<array{registry_key: string, entity_type: string, entity_id: int|null, page_id: int|null}>  $orphans
     * @return array<string, int>
     */
    private function metrics(array $orphans): array
    {
        $sourceCounts = PageRegistry::query()
            ->selectRaw('source, COUNT(*) as aggregate')
            ->groupBy('source')
            ->pluck('aggregate', 'source');

        $protectedStates = [
            AdminLifecycleState::Disabled->value,
            AdminLifecycleState::Archived->value,
            AdminLifecycleState::DeletedByAdmin->value,
        ];

        $registryRows = (int) PageRegistry::query()->count();
        $cmsPages = (int) Page::query()->count();
        $distinctRegistryPages = (int) PageRegistry::query()
            ->whereNotNull('page_id')
            ->distinct('page_id')
            ->count('page_id');
        $locationMappings = (int) ServiceLocationPage::query()->count();
        $expectedLocationPages = (int) DB::table('service_pincodes')
            ->join('pin_codes', 'pin_codes.id', '=', 'service_pincodes.pincode_id')
            ->whereNull('pin_codes.deleted_at')
            ->count();

        return [
            'registry_rows' => $registryRows,
            'pages' => $cmsPages,
            'distinct_registry_pages' => $distinctRegistryPages,
            'location_mappings' => $locationMappings,
            'expected_location_pages' => $expectedLocationPages,
            'synced_pages' => (int) PageRegistry::query()->whereNotNull('page_id')->count(),
            'generated' => (int) ($sourceCounts['generated'] ?? 0),
            'manual' => (int) ($sourceCounts['manual'] ?? 0),
            'planned' => (int) ($sourceCounts['planned'] ?? 0),
            'orphan_registry' => count($orphans),
            'tombstones' => Schema::hasTable('admin_deletion_tombstones')
                ? (int) AdminDeletionTombstone::query()->count()
                : 0,
            'protected_pages' => (int) Page::query()->whereIn('lifecycle_state', $protectedStates)->count(),
            'admin_overrides' => ServicePageOverrides::countPagesWithAdminAuthority(),
        ];
    }

    /**
     * @return array{status: string, at: ?string, counts: ?array<string, int>, label: string}
     */
    private function lastSyncStatus(): array
    {
        $at = Cache::get('governance.registry.last_sync_at');
        $counts = Cache::get('governance.registry.last_sync_counts');
        $status = (string) (Cache::get('governance.registry.last_sync_status') ?? 'unknown');

        if ($at === null && Schema::hasTable('page_registry')) {
            $latest = PageRegistry::query()->max('updated_at');
            if ($latest !== null) {
                $at = (string) $latest;
                $status = 'inferred';
            }
        }

        if ($at === null) {
            return [
                'status' => 'never',
                'at' => null,
                'counts' => null,
                'label' => __('Never synced via dashboard or artisan'),
            ];
        }

        return [
            'status' => $status,
            'at' => is_string($at) ? $at : null,
            'counts' => is_array($counts) ? $counts : null,
            'label' => match ($status) {
                'ok' => __('Last sync completed successfully'),
                'inferred' => __('Inferred from latest registry row update'),
                default => __('Sync status unknown'),
            },
        ];
    }

    /**
     * @return list<array{component: string, status: string, enabled: bool, detail: string}>
     */
    private function governanceStatus(): array
    {
        return [
            [
                'component' => 'AdminAuthorityGuard',
                'status' => config('governance.enforce_admin_authority', true) ? __('Active') : __('Bypassed'),
                'enabled' => (bool) config('governance.enforce_admin_authority', true),
                'detail' => __('Blocks automated recreation of admin-deleted records'),
            ],
            [
                'component' => 'AdminDeletionGuard',
                'status' => Schema::hasTable('admin_deletion_tombstones') ? __('Active') : __('Unavailable'),
                'enabled' => Schema::hasTable('admin_deletion_tombstones'),
                'detail' => __('Service tombstones prevent provisioner recreation'),
            ],
            [
                'component' => 'AutomatedWriteAuditLogger',
                'status' => config('governance.audit_automated_writes', true) ? __('Active') : __('Disabled'),
                'enabled' => (bool) config('governance.audit_automated_writes', true),
                'detail' => Schema::hasTable('automated_write_audits')
                    ? __(':count audit row(s) logged', ['count' => number_format((int) DB::table('automated_write_audits')->count())])
                    : __('Audit table not migrated'),
            ],
            [
                'component' => 'DownstreamArtifactPurger',
                'status' => __('Active'),
                'enabled' => true,
                'detail' => __('Purges registry rows when database entities are gone'),
            ],
        ];
    }

    /**
     * @return array{last_success_at: ?string, last_failure: ?array{scope: string, key: string, message: string, at: string}}
     */
    private function cascadeStatus(): array
    {
        $failure = Cache::get('catalog_cascade.last_failure');

        return [
            'last_success_at' => Cache::get('catalog_cascade.last_success_at'),
            'last_failure' => is_array($failure) ? $failure : null,
        ];
    }

    /**
     * @return array{universal_page_registry: string, sync_command: string, purge_command: string, sync_available: bool, purge_available: bool}
     */
    private function registryStatus(): array
    {
        $commands = array_keys(Artisan::all());

        return [
            'universal_page_registry' => class_exists(UniversalPageRegistry::class) ? __('Operational') : __('Missing'),
            'sync_command' => 'medca:sync-page-registry',
            'purge_command' => 'medca:purge-registry-orphans',
            'sync_available' => in_array('medca:sync-page-registry', $commands, true),
            'purge_available' => in_array('medca:purge-registry-orphans', $commands, true),
        ];
    }

    /**
     * @param  list<array{registry_key: string, entity_type: string, entity_id: int|null, page_id: int|null}>  $orphans
     * @return array{
     *     aligned: bool,
     *     checks: list<array{label: string, ok: bool, value: string, detail: string}>,
     *     issues: list<array{type: string, registry_key: string, detail: string}>
     * }
     */
    private function healthAnalysis(array $orphans): array
    {
        $issues = [];
        $pagesMissingRegistry = 0;
        $registryMissingPages = 0;
        $mappingsMissingRegistry = 0;
        $staleRows = 0;

        Page::query()->select(['id', 'slug'])->orderBy('id')->chunkById(100, function ($pages) use (&$pagesMissingRegistry): void {
            foreach ($pages as $page) {
                if (! PageRegistry::query()->where('registry_key', 'page:'.$page->slug)->exists()) {
                    $pagesMissingRegistry++;
                }
            }
        });

        PageRegistry::query()
            ->whereNotNull('page_id')
            ->select(['id', 'registry_key', 'page_id'])
            ->orderBy('id')
            ->chunkById(100, function ($entries) use (&$registryMissingPages, &$staleRows): void {
                foreach ($entries as $entry) {
                    $page = Page::query()->find($entry->page_id);
                    if ($page === null) {
                        $registryMissingPages++;
                        $issues[] = [
                            'type' => 'missing_page',
                            'registry_key' => $entry->registry_key,
                            'detail' => __('Registry references missing page #:id', ['id' => $entry->page_id]),
                        ];

                        continue;
                    }

                    $expectedKey = 'page:'.$page->slug;
                    if ($entry->registry_key !== $expectedKey && str_starts_with($entry->registry_key, 'page:')) {
                        $staleRows++;
                        $issues[] = [
                            'type' => 'stale_registry',
                            'registry_key' => $entry->registry_key,
                            'detail' => __('Expected registry key :key', ['key' => $expectedKey]),
                        ];
                    }
                }
            });

        ServiceLocationPage::query()
            ->with(['service', 'pincode'])
            ->whereNotNull('page_id')
            ->orderBy('id')
            ->chunkById(100, function ($mappings) use (&$mappingsMissingRegistry, &$issues): void {
                foreach ($mappings as $mapping) {
                    $code = $mapping->service?->service_code ?? 'unknown';
                    $pin = $mapping->pincode?->pincode ?? 'unknown';
                    $key = 'location:'.$code.':'.$pin;

                    if (! PageRegistry::query()->where('registry_key', $key)->exists()) {
                        $mappingsMissingRegistry++;
                        $issues[] = [
                            'type' => 'missing_mapping',
                            'registry_key' => $key,
                            'detail' => __('Location mapping #:id has no registry row', ['id' => $mapping->id]),
                        ];
                    }
                }
            });

        foreach ($orphans as $orphan) {
            $issues[] = [
                'type' => 'orphan_entity',
                'registry_key' => $orphan['registry_key'],
                'detail' => __('Orphan :type entity', ['type' => $orphan['entity_type']]),
            ];
        }

        $checks = [
            [
                'label' => __('Registry aligned with pages table'),
                'ok' => $pagesMissingRegistry === 0 && $registryMissingPages === 0,
                'value' => $pagesMissingRegistry === 0 && $registryMissingPages === 0
                    ? __('Aligned')
                    : __('Drift detected'),
                'detail' => __(':missing_registry page(s) without registry · :missing_pages registry row(s) without pages', [
                    'missing_registry' => number_format($pagesMissingRegistry),
                    'missing_pages' => number_format($registryMissingPages),
                ]),
            ],
            [
                'label' => __('Missing mappings'),
                'ok' => $mappingsMissingRegistry === 0,
                'value' => number_format($mappingsMissingRegistry),
                'detail' => __('Location mappings without registry rows'),
            ],
            [
                'label' => __('Orphan entities'),
                'ok' => count($orphans) === 0,
                'value' => number_format(count($orphans)),
                'detail' => __('Registry rows whose database entities no longer exist'),
            ],
            [
                'label' => __('Stale registry rows'),
                'ok' => $staleRows === 0,
                'value' => number_format($staleRows),
                'detail' => __('Page registry keys that no longer match page slugs'),
            ],
        ];

        $aligned = collect($checks)->every(fn (array $check): bool => $check['ok']);

        return [
            'aligned' => $aligned,
            'checks' => $checks,
            'issues' => array_slice($issues, 0, 100),
        ];
    }
}
