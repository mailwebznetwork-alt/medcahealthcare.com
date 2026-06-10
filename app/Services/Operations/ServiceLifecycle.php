<?php

namespace App\Services\Operations;

use App\Enums\PublishStatus;
use App\Models\Service;
use App\Services\Governance\AdminDeletionGuard;
use App\Services\Governance\MasterDataAudit;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Models\PageElement;
use App\Models\PageSeo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final class ServiceLifecycle
{
    public function __construct(
        private readonly ServiceMasterOrchestrator $orchestrator,
        private readonly AdminDeletionGuard $deletionGuard,
        private readonly DownstreamArtifactPurger $purger,
        private readonly MasterDataAudit $audit,
    ) {}

    public function duplicate(Service $service): Service
    {
        $service->loadMissing(['seo', 'faqs', 'schema', 'pincodes', 'categories']);

        return DB::transaction(function () use ($service): Service {
            $new = $service->replicate();
            $new->service_code = $service->service_code.'_copy_'.time();
            $new->detail_page_id = null;
            $new->publish_status = PublishStatus::Draft;
            $new->featured_image = null;
            $new->icon = null;
            $new->gallery = [];
            $new->save();

            if ($service->seo) {
                $seoAttrs = collect($service->seo->replicate()->getAttributes())
                    ->except(['id', 'service_id', 'created_at', 'updated_at'])
                    ->all();
                $new->seo()->updateOrCreate(['service_id' => $new->id], $seoAttrs);
            }

            foreach ($service->faqs as $faq) {
                $row = $faq->replicate();
                $row->service_id = $new->id;
                $row->save();
            }

            if ($service->schema) {
                $schemaAttrs = collect($service->schema->replicate()->getAttributes())
                    ->except(['id', 'service_id', 'created_at', 'updated_at'])
                    ->all();
                $new->schema()->updateOrCreate(['service_id' => $new->id], $schemaAttrs);
            }

            $new->pincodes()->sync($service->pincodes->pluck('id')->all());
            $new->categories()->sync($service->categories->pluck('id')->all());

            $new = $new->fresh(['pincodes', 'seo', 'faqs', 'schema']);
            $this->orchestrator->sync($new);

            return $new;
        });
    }

    public function delete(Service $service): void
    {
        $this->deleteMany(collect([$service]), 'ui');
        $this->purger->purgeAfterCatalogEntityChange();
    }

    /**
     * @param  Collection<int, Service>|iterable<int, Service>  $services
     */
    public function deleteMany(iterable $services, string $source = 'bulk'): int
    {
        $collection = $services instanceof Collection ? $services : collect($services);

        if ($collection->isEmpty()) {
            return 0;
        }

        $serviceIds = $collection->pluck('id')->all();
        $deleted = 0;

        Service::withoutEvents(function () use ($collection, $serviceIds, $source, &$deleted): void {
            DB::transaction(function () use ($collection, $serviceIds, $source, &$deleted): void {
                $this->orchestrator->bulkTeardown($serviceIds, $collection);
                $this->purgeGrowthArtifactsForServices($collection);

                foreach ($collection as $service) {
                    $this->deleteServiceMedia($service);
                    $this->deletionGuard->recordServiceDeletion($service, $source);
                    $service->delete();
                    $this->audit->serviceDeleted($service, $source);
                    $deleted++;
                }
            });
        });

        if ($deleted > 0 && $source === 'bulk') {
            $this->purger->purgeAfterBulkCatalogDeletion();
        }

        return $deleted;
    }

    /**
     * @param  Collection<int, Service>  $services
     */
    private function purgeGrowthArtifactsForServices(Collection $services): void
    {
        $slugPaths = $services
            ->map(fn (Service $service): string => 'services/'.ltrim((string) $service->service_code, '/'))
            ->unique()
            ->values()
            ->all();

        if ($slugPaths === []) {
            return;
        }

        if (Schema::hasTable('page_seo')) {
            PageSeo::query()->whereIn('page_slug', $slugPaths)->delete();
        }

        if (Schema::hasTable('page_elements')) {
            PageElement::query()->whereIn('page_slug', $slugPaths)->delete();
        }
    }

    private function deleteServiceMedia(Service $service): void
    {
        $this->deletePublicPath($service->featured_image);
        $this->deletePublicPath($service->icon);

        if (! is_array($service->gallery)) {
            return;
        }

        foreach ($service->gallery as $path) {
            $this->deletePublicPath($path);
        }
    }

    private function deletePublicPath(?string $path): void
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
