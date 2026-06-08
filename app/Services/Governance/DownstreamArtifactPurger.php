<?php

namespace App\Services\Governance;

use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceLocationPage;
use App\Models\SubService;
use Illuminate\Support\Facades\Cache;

/**
 * Purges registry, cache, and downstream artifacts when database records no longer exist.
 * Never recreates missing database records.
 */
class DownstreamArtifactPurger
{
    public function __construct(
        private readonly AutomatedWriteAuditLogger $audit,
    ) {}

    /**
     * @return array{registry_removed: int, issues: list<string>}
     */
    public function purgeRegistryOrphans(): array
    {
        $removed = 0;
        $issues = [];

        PageRegistry::query()->orderBy('id')->each(function (PageRegistry $entry) use (&$removed, &$issues): void {
            if ($this->registryEntryIsOrphan($entry)) {
                $key = $entry->registry_key;
                $entry->delete();
                $removed++;

                $this->audit->log(
                    process: 'DownstreamArtifactPurger',
                    action: 'purge_registry_orphan',
                    table: 'page_registry',
                    recordId: null,
                    recordKey: $key,
                    outcome: 'applied',
                    reason: 'Database entity no longer exists; registry follows database.',
                );

                $issues[] = "Removed orphan registry key: {$key}";
            }
        });

        return ['registry_removed' => $removed, 'issues' => $issues];
    }

    public function purgeForDeletedPage(Page $page): void
    {
        PageRegistry::query()
            ->where(function ($query) use ($page): void {
                $query->where('page_id', $page->id)
                    ->orWhere(fn ($q) => $q->where('entity_type', 'page')->where('entity_id', $page->id));
            })
            ->delete();

        $this->forgetPageCaches();
    }

    public function purgeForDeletedService(Service $service): void
    {
        PageRegistry::query()
            ->where('entity_type', 'service')
            ->where('entity_id', $service->id)
            ->delete();

        PageRegistry::query()
            ->where('registry_key', 'service:'.$service->service_code)
            ->delete();

        $this->forgetPageCaches();
    }

    public function purgeForDeletedLocationMapping(ServiceLocationPage $mapping): void
    {
        $mapping->loadMissing(['service', 'pincode']);

        $code = $mapping->service?->service_code ?? 'unknown';
        $pin = $mapping->pincode?->pincode ?? 'unknown';

        PageRegistry::query()
            ->where('registry_key', 'location:'.$code.':'.$pin)
            ->delete();

        if ($mapping->page_id !== null) {
            PageRegistry::query()->where('page_id', $mapping->page_id)->delete();
        }
    }

    public function purgeForDeletedCategory(ServiceCategory $category): void
    {
        PageRegistry::query()
            ->where('entity_type', 'category')
            ->where('entity_id', $category->id)
            ->delete();

        PageRegistry::query()
            ->where('registry_key', 'category:'.$category->publicSlug())
            ->delete();

        $this->forgetPageCaches();
    }

    public function purgeForDeletedSubService(SubService $sub): void
    {
        $sub->loadMissing('service');

        PageRegistry::query()
            ->where('entity_type', 'sub_service')
            ->where('entity_id', $sub->id)
            ->delete();

        PageRegistry::query()
            ->where('registry_key', 'sub_service:'.$sub->service?->service_code.':'.$sub->sub_service_code)
            ->delete();

        $this->forgetPageCaches();
    }

    public function purgeForDeletedPinCode(PinCode $pinCode): void
    {
        ServiceLocationPage::query()
            ->where('pincode_id', $pinCode->id)
            ->with(['service', 'pincode'])
            ->each(function (ServiceLocationPage $mapping): void {
                $this->purgeForDeletedLocationMapping($mapping);
            });

        $this->forgetPageCaches();
    }

    private function registryEntryIsOrphan(PageRegistry $entry): bool
    {
        return match ($entry->entity_type) {
            'page' => $entry->page_id === null || ! Page::query()->whereKey($entry->page_id)->exists(),
            'service' => $entry->entity_id === null || ! Service::query()->whereKey($entry->entity_id)->exists(),
            'category' => $entry->entity_id === null || ! ServiceCategory::query()->whereKey($entry->entity_id)->exists(),
            'sub_service' => $entry->entity_id === null || ! SubService::query()->whereKey($entry->entity_id)->exists(),
            'location' => $entry->entity_id === null || ! ServiceLocationPage::query()->whereKey($entry->entity_id)->exists(),
            default => $entry->page_id !== null && ! Page::query()->whereKey($entry->page_id)->exists(),
        };
    }

    private function forgetPageCaches(): void
    {
        try {
            Cache::tags(['pages', 'sitemap', 'registry'])->flush();
        } catch (\Throwable) {
            // Tag driver may be unavailable; best-effort only.
        }
    }
}
