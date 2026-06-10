<?php

namespace App\Services\Operations;

use App\Enums\AdminLifecycleState;
use App\Enums\PageCategory;
use App\Enums\PageLayoutMode;
use App\Enums\PublishStatus;
use App\Models\Block;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\Service;
use Illuminate\Support\Collection;
use App\Services\Governance\AdminAuthorityGuard;
use App\Services\Governance\AdminDeletionGuard;
use App\Support\ServicePageOverrides;

class ServiceDetailPageProvisioner
{
    public const string DEFAULT_PAGE_CONTENT = "{{block:service-detail-hero}}\n{{block:service-detail-body}}\n{{block:service-detail-areas}}";

    public function __construct(
        private readonly ServiceDetailPageSeoSync $seoSync,
    ) {}

    public static function serviceCodeFromPageSlug(string $slug): ?string
    {
        $pattern = (string) config('public_pages.service_detail_page_slug_pattern', 'service-{code}');
        $prefix = str_replace('{code}', '', $pattern);

        if ($prefix === '' || ! str_starts_with($slug, $prefix)) {
            return null;
        }

        $code = substr($slug, strlen($prefix));

        return $code !== '' ? $code : null;
    }

    public function suggestedSlug(Service $service): string
    {
        $pattern = (string) config('public_pages.service_detail_page_slug_pattern', 'service-{code}');

        return str_replace('{code}', $service->service_code, $pattern);
    }

    public function findPageBySuggestedSlug(Service $service): ?Page
    {
        return Page::query()
            ->where('slug', $this->suggestedSlug($service))
            ->first();
    }

    /**
     * Create (or reuse) a canvas Site Architect page for /services/{code} and link the service.
     */
    public function provision(Service $service): Page
    {
        if (! app(AdminDeletionGuard::class)->canProvisionService($service, $service->service_code, 'ServiceDetailPageProvisioner::provision')) {
            throw new \RuntimeException("Cannot provision deleted service: {$service->service_code}");
        }

        $slug = $this->suggestedSlug($service);

        $page = Page::query()->where('slug', $slug)->first();

        if ($page === null) {
            $this->ensureStarterBlocks();

            $page = Page::query()->create([
                'title' => $service->title,
                'slug' => $slug,
                'content' => self::DEFAULT_PAGE_CONTENT,
                'is_active' => true,
                'layout_mode' => PageLayoutMode::Canvas,
                'page_category' => PageCategory::Service,
                'page_source' => 'generated',
                'registry_owner' => 'operations_service',
                'meta_title' => $service->seo?->meta_title ?: $service->title,
            ]);
        } elseif (! ServicePageOverrides::contentOverride($page)) {
            $content = trim((string) $page->content);

            if ($content === '' || ! str_contains($content, 'service-detail-hero')) {
                $page->update(['content' => self::DEFAULT_PAGE_CONTENT]);
            } elseif (! str_contains($content, 'service-detail-body')) {
                $page->update(['content' => $this->injectDetailBodyBlock($content)]);
            }
        }

        if (
            ! ServicePageOverrides::titleOverride($page)
            && $page->title === $service->title.' — '.__('Service detail')
        ) {
            $page->update(['title' => $service->title]);
        }

        if ($service->detail_page_id !== $page->id) {
            $service->forceFill(['detail_page_id' => $page->id])->save();
        }

        $service->loadMissing(['seo', 'faqs', 'schema']);
        $this->seoSync->migrateFromServiceIfEmpty($service, $page);

        return $page->fresh(['faqs']);
    }

    /**
     * Create or update the owned Site Architect page for this service (slug service-{code}).
     */
    public function syncFromService(Service $service, ?string $previousServiceCode = null): Page
    {
        $service->loadMissing(['seo', 'faqs', 'schema']);

        $page = $this->findOwnedPage($service, $previousServiceCode);

        if ($page === null) {
            return $this->provision($service);
        }

        $targetSlug = $this->uniquePageSlug($this->suggestedSlug($service), $page->id);

        $attributes = ServicePageOverrides::filterAutomatedAttributes($page, [
            'title' => $service->title,
            'slug' => $targetSlug,
            'page_category' => PageCategory::Service,
            'is_active' => $service->is_active && $service->publish_status === PublishStatus::Published,
            'meta_title' => $service->seo?->meta_title ?: $service->title,
            'meta_description' => $service->seo?->meta_description,
            'h1' => $service->seo?->h1 ?: $service->title,
            'canonical_url' => $service->seo?->canonical_url ?: $service->publicUrl(),
        ]);

        if ($attributes !== []) {
            $page->update($attributes);
        }

        if ($service->detail_page_id !== $page->id) {
            $service->forceFill(['detail_page_id' => $page->id])->save();
        }

        $this->seoSync->migrateFromServiceIfEmpty($service, $page->fresh());

        return $page->fresh();
    }

    /**
     * Remove the service-owned detail page (service-{code} or linked detail_page_id).
     */
    public function deleteOwnedPage(Service $service): void
    {
        $this->bulkDeleteOwnedPagesForServices(collect([$service]));
    }

    /**
     * @param  Collection<int, Service>  $services
     */
    public function bulkDeleteOwnedPagesForServices(Collection $services): void
    {
        if ($services->isEmpty()) {
            return;
        }

        $serviceIds = $services->pluck('id')->all();
        $detailPageIds = $services->pluck('detail_page_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $suggestedSlugs = $services
            ->map(fn (Service $service): string => $this->suggestedSlug($service))
            ->unique()
            ->values()
            ->all();

        $extraPageIds = $suggestedSlugs === []
            ? []
            : Page::query()->whereIn('slug', $suggestedSlugs)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $pageIds = array_values(array_unique(array_merge($detailPageIds, $extraPageIds)));

        $registryKeys = $services
            ->map(fn (Service $service): string => 'service:'.$service->service_code)
            ->unique()
            ->values()
            ->all();

        if ($registryKeys !== []) {
            PageRegistry::query()->whereIn('registry_key', $registryKeys)->delete();
        }

        PageRegistry::query()
            ->where('entity_type', 'service')
            ->whereIn('entity_id', $serviceIds)
            ->delete();

        if ($pageIds !== []) {
            PageRegistry::query()->whereIn('page_id', $pageIds)->delete();
            Page::query()->whereIn('id', $pageIds)->delete();
        }
    }

    public function pageIsOwnedByService(Page $page, Service $service): bool
    {
        if ($service->detail_page_id !== null && (int) $service->detail_page_id === (int) $page->id) {
            return true;
        }

        $expectedSlug = $this->suggestedSlug($service);

        if ($page->slug === $expectedSlug) {
            return true;
        }

        return self::serviceCodeFromPageSlug((string) $page->slug) === $service->service_code;
    }

    private function findOwnedPage(Service $service, ?string $previousServiceCode): ?Page
    {
        if ($service->detail_page_id !== null) {
            $linked = Page::query()->find($service->detail_page_id);
            if ($linked !== null) {
                return $linked;
            }
        }

        if ($previousServiceCode !== null && $previousServiceCode !== '') {
            $pattern = (string) config('public_pages.service_detail_page_slug_pattern', 'service-{code}');
            $oldSlug = str_replace('{code}', $previousServiceCode, $pattern);
            $byOldSlug = Page::query()->where('slug', $oldSlug)->first();
            if ($byOldSlug !== null) {
                return $byOldSlug;
            }
        }

        return $this->findPageBySuggestedSlug($service);
    }

    private function uniquePageSlug(string $base, ?int $exceptPageId = null): string
    {
        $slug = $base;
        $suffix = 1;

        while (
            Page::query()
                ->when($exceptPageId !== null, fn ($q) => $q->whereKeyNot($exceptPageId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return array{created: int, linked: int, skipped: int}
     */
    public function provisionAll(bool $onlyWithoutLinkedPage = false): array
    {
        $this->syncStarterBlocks();

        $created = 0;
        $linked = 0;
        $skipped = 0;

        Service::query()->orderBy('id')->each(function (Service $service) use ($onlyWithoutLinkedPage, &$created, &$linked, &$skipped): void {
            if ($onlyWithoutLinkedPage && $service->detail_page_id !== null) {
                $skipped++;

                return;
            }

            $existed = Page::query()->where('slug', $this->suggestedSlug($service))->exists();
            $page = $this->provision($service);
            if (! $existed) {
                $created++;
            } else {
                $linked++;
            }
        });

        return compact('created', 'linked', 'skipped');
    }

    public function syncStarterBlocks(): void
    {
        $guard = app(AdminAuthorityGuard::class);

        foreach ([
            'service-detail-hero' => [
                'block_name' => 'Service detail — hero (uses $service)',
                'code' => "@include('blocks.services.service-detail-hero')",
                'block_type' => 'Hero',
            ],
            'service-detail-body' => [
                'block_name' => 'Service detail — full content (uses $service)',
                'code' => "@include('blocks.services.service-detail-body')",
                'block_type' => 'Sections',
            ],
            'service-detail-areas' => [
                'block_name' => 'Service detail — areas we cover',
                'code' => "@include('blocks.services.service-detail-areas')",
                'block_type' => 'Text',
            ],
            'service-detail-related' => [
                'block_name' => 'Service detail — related services (Insert service tokens)',
                'code' => "@include('blocks.services.service-detail-related')",
                'block_type' => 'Service Grid',
            ],
            'location-geo-enrichment' => [
                'block_name' => 'Location page — geo enrichment (pincode dataset)',
                'code' => "@include('blocks.locations.location-geo-enrichment')",
                'block_type' => 'Location',
            ],
        ] as $slug => $fields) {
            if (! $guard->canRecreateBlockSlug($slug, 'ServiceDetailPageProvisioner::syncStarterBlocks')) {
                continue;
            }

            Block::query()->updateOrCreate(
                ['block_slug' => $slug],
                [
                    ...$fields,
                    'is_active' => true,
                    'is_managed' => true,
                    'lifecycle_state' => AdminLifecycleState::SystemManaged->value,
                ]
            );
        }

        $hero = Block::query()->where('block_slug', 'service-detail-hero')->first();
        if ($hero !== null) {
            $settings = is_array($hero->settings_json) ? $hero->settings_json : [];
            unset($settings['content']);
            $hero->forceFill(['settings_json' => $settings])->save();
        }
    }

    private function ensureStarterBlocks(): void
    {
        $this->syncStarterBlocks();
    }

    private function injectDetailBodyBlock(string $content): string
    {
        if (str_contains($content, '{{block:service-detail-body}}')) {
            return $content;
        }

        if (str_contains($content, '{{block:service-detail-hero}}')) {
            return str_replace(
                '{{block:service-detail-hero}}',
                "{{block:service-detail-hero}}\n{{block:service-detail-body}}",
                $content
            );
        }

        return "{{block:service-detail-body}}\n".$content;
    }
}
