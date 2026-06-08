<?php

namespace App\Services\Operations;

use App\Enums\PublishStatus;
use App\Models\Service;
use App\Services\Governance\AdminDeletionGuard;
use App\Services\Governance\MasterDataAudit;
use App\Services\Governance\DownstreamArtifactPurger;
use Illuminate\Support\Facades\DB;
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
        DB::transaction(function () use ($service): void {
            $this->deletePublicPath($service->featured_image);
            $this->deletePublicPath($service->icon);
            if (is_array($service->gallery)) {
                foreach ($service->gallery as $path) {
                    $this->deletePublicPath($path);
                }
            }

            $this->deletionGuard->recordServiceDeletion($service, 'ui');
            $this->orchestrator->teardown($service);
            $this->purger->purgeForDeletedService($service);
            $service->delete();
            $this->audit->serviceDeleted($service, 'ui');
        });
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
