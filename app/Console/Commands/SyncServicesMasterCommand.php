<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\Operations\ServiceMasterOrchestrator;
use Illuminate\Console\Command;

class SyncServicesMasterCommand extends Command
{
    protected $signature = 'services:sync-master {--service= : Service code to sync only}';

    protected $description = 'Backfill schema, scores, pages, location URLs, redirects, and internal links.';

    public function handle(ServiceMasterOrchestrator $orchestrator): int
    {
        $code = $this->option('service');
        $query = Service::query()->orderBy('id');

        if (is_string($code) && $code !== '') {
            $query->where('service_code', $code);
        }

        $count = 0;
        $query->each(function (Service $service) use ($orchestrator, &$count): void {
            $orchestrator->sync($service);
            $count++;
            $this->line(__('Synced :title (:code)', ['title' => $service->title, 'code' => $service->service_code]));
        });

        $resolver = app(\App\Services\Operations\PageCategoryResolver::class);
        \App\Models\Page::query()->orderBy('id')->each(fn (\App\Models\Page $page) => $resolver->applyToPage($page));

        $mediaRefs = app(\App\Services\Media\MediaUsagesIndexer::class)->reindexAll();

        $this->info(__('Services master sync complete for :count service(s). Page categories refreshed.', ['count' => $count]));
        $this->comment(__('Public URLs: /services/{code} and /services/{code}/{location-slug}'));
        $this->comment(__('Legacy /p/service-* → 301 redirects registered. Media usages indexed: :refs.', ['refs' => $mediaRefs]));
        $this->comment(__('Run npm run build if assets changed.'));

        return self::SUCCESS;
    }
}
