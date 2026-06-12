<?php

namespace App\Services\Operations;

use App\Enums\PageCategory;
use App\Enums\PageLayoutMode;
use App\Models\Page;
use App\Models\PageRegistry;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Governance\DownstreamArtifactPurger;
use App\Services\Import\ImportSideEffectsGate;
use App\Services\Seo\LocalityContextResolver;
use App\Support\ServicePageOverrides;
use Illuminate\Support\Str;

class ServiceLocationPageProvisioner
{
    private static bool $starterBlocksSyncedForBulk = false;

    public static function resetBulkOptimizations(): void
    {
        self::$starterBlocksSyncedForBulk = false;
    }

    public function __construct(
        private readonly ServiceMasterPageSync $masterPageSync,
        private readonly PageCategoryResolver $categoryResolver,
        private readonly ServicePublicUrlBuilder $urlBuilder,
        private readonly ServiceSchemaGenerator $schemaGenerator,
        private readonly LocationPageQualityScorer $qualityScorer,
        private readonly ServiceLocationTemplateResolver $templates,
    ) {}

    /** Internal CMS page slug (Site Architect). */
    public function cmsPageSlug(Service $service, PinCode $pin): string
    {
        $pattern = (string) config('services_master.location_page_slug_pattern', 'service-{code}-loc-{pincode}');

        return str_replace(
            ['{code}', '{pincode}'],
            [$service->service_code, $pin->pincode],
            $pattern
        );
    }

    public function locationSlug(Service $service, PinCode $pin): string
    {
        return $this->urlBuilder->locationSlugForPin($pin);
    }

    public function locationTitle(Service $service, PinCode $pin): string
    {
        return $this->templates->locationTitle($service, $pin);
    }

    /**
     * @return array{created: int, updated: int, removed: int}
     */
    public function syncAllForService(Service $service, bool $refreshExisting = false): array
    {
        $service->loadMissing(['pincodes', 'seo', 'faqs', 'schema', 'locationPages.pincode']);

        if (! ServiceGeneratedPageEligibility::serviceMayHavePages($service)) {
            $removed = $this->bulkDeleteLocationArtifactsForServiceIds([$service->id]);

            return ['created' => 0, 'updated' => 0, 'removed' => $removed];
        }

        if (! app(ImportSideEffectsGate::class)->suppressed() || ! self::$starterBlocksSyncedForBulk) {
            app(ServiceDetailPageProvisioner::class)->syncStarterBlocks();
            if (app(ImportSideEffectsGate::class)->suppressed()) {
                self::$starterBlocksSyncedForBulk = true;
            }
        }

        $created = 0;
        $updated = 0;
        $removed = 0;
        $activePinIds = $service->pincodes->pluck('id')->all();

        $bulkCreateOnly = ! $refreshExisting && app(ImportSideEffectsGate::class)->suppressed();
        $existingByPin = ServiceLocationPage::query()
            ->where('service_id', $service->id)
            ->get()
            ->keyBy('pincode_id');

        foreach ($service->pincodes as $pin) {
            $mapping = $existingByPin->get($pin->id);

            if ($bulkCreateOnly && $mapping?->page_id !== null) {
                continue;
            }

            $existed = $mapping !== null;

            $page = $this->provisionOne($service, $pin, fast: $bulkCreateOnly && ! $existed);

            if ($page === null) {
                if ($existed) {
                    $removed++;
                }

                continue;
            }

            if ($existed) {
                $updated++;
            } else {
                $created++;
            }
        }

        $orphans = ServiceLocationPage::query()
            ->where('service_id', $service->id)
            ->when($activePinIds !== [], fn ($q) => $q->whereNotIn('pincode_id', $activePinIds))
            ->with('page')
            ->get();

        foreach ($orphans as $row) {
            $this->removeMappingAndPage($row);
            $removed++;
        }

        return compact('created', 'updated', 'removed');
    }

    /**
     * @return array{updated: int}
     */
    public function syncAllForPincode(PinCode $pin): array
    {
        $pin->loadMissing('services');
        $updated = 0;

        foreach ($pin->services as $service) {
            if (! $service->pincodes()->whereKey($pin->id)->exists()) {
                continue;
            }

            $this->provisionOne($service, $pin);
            $updated++;
        }

        return ['updated' => $updated];
    }

    public function deleteAllForPincode(PinCode $pin): int
    {
        return $this->bulkDeleteLocationArtifactsForPinIds([$pin->id]);
    }

    /**
     * @param  list<int>  $pinIds
     */
    public function bulkDeleteLocationArtifactsForPinIds(array $pinIds): int
    {
        if ($pinIds === []) {
            return 0;
        }

        $mappings = ServiceLocationPage::query()
            ->whereIn('pincode_id', $pinIds)
            ->with(['service:id,service_code', 'pincode:id,pincode'])
            ->get();

        if ($mappings->isEmpty()) {
            return 0;
        }

        $pageIds = $mappings->pluck('page_id')->filter()->unique()->values()->all();

        $registryKeys = $mappings
            ->map(fn (ServiceLocationPage $mapping): ?string => $mapping->service && $mapping->pincode
                ? 'location:'.$mapping->service->service_code.':'.$mapping->pincode->pincode
                : null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($registryKeys !== []) {
            PageRegistry::query()->whereIn('registry_key', $registryKeys)->delete();
        }

        if ($pageIds !== []) {
            PageRegistry::query()->whereIn('page_id', $pageIds)->delete();
            Page::query()->whereIn('id', $pageIds)->delete();
        }

        ServiceLocationPage::query()->whereIn('pincode_id', $pinIds)->delete();

        return $mappings->count();
    }

    public function removeMappingAndPage(ServiceLocationPage $mapping): void
    {
        $mapping->loadMissing(['page', 'service', 'pincode']);

        app(DownstreamArtifactPurger::class)->purgeForDeletedLocationMapping($mapping);

        if ($mapping->page !== null) {
            $mapping->page->delete();
        }

        if ($mapping->exists) {
            $mapping->delete();
        }
    }

    public function provisionOne(Service $service, PinCode $pin, bool $fast = false): ?Page
    {
        if (! ServiceGeneratedPageEligibility::locationMappingMayExist($service, $pin)) {
            $mapping = ServiceLocationPage::query()
                ->where('service_id', $service->id)
                ->where('pincode_id', $pin->id)
                ->with('page')
                ->first();

            if ($mapping !== null) {
                $this->removeMappingAndPage($mapping);
            }

            return null;
        }

        if ($fast) {
            return $this->provisionOneBulkFast($service, $pin);
        }

        $pin->loadMissing(['landmarks', 'hospitals', 'locationFaqs', 'nearbyAreas']);

        $mapping = ServiceLocationPage::query()
            ->where('service_id', $service->id)
            ->where('pincode_id', $pin->id)
            ->first();

        $cmsSlug = $this->uniqueSlug($this->cmsPageSlug($service, $pin), $mapping?->page_id);
        $locationSlug = $this->locationSlug($service, $pin);
        $citySlug = $this->urlBuilder->citySlugForPin($pin);
        $title = $this->locationTitle($service, $pin);
        $h2 = $this->templates->locationH2($service, $pin);
        $h3 = $this->templates->locationH3($service, $pin);
        $canonical = $this->urlBuilder->locationUrlForPin($service, $pin);

        $page = $mapping?->page;

        if ($page === null) {
            $page = Page::query()->where('slug', $cmsSlug)->first();
        }

        $content = (string) config('services_master.location_page_content', ServiceDetailPageProvisioner::DEFAULT_PAGE_CONTENT);
        $intro = $this->localIntro($service, $pin);
        $description = $this->templates->localDescription($service, $pin);
        if ($page === null) {
            $page = Page::query()->create([
                'title' => $title,
                'slug' => $cmsSlug,
                'content' => $content,
                'is_active' => true,
                'layout_mode' => PageLayoutMode::Canvas,
                'page_category' => PageCategory::Location,
                'page_source' => 'generated',
                'registry_owner' => 'operations_location_matrix',
                'meta_title' => $this->locationMetaTitle($service, $pin),
                'meta_description' => $this->localMetaDescription($service, $pin),
                'h1' => $title,
                'heading_h2' => $h2 !== null ? [$h2] : null,
                'heading_h3' => $h3 !== null ? [$h3] : null,
                'canonical_url' => $canonical,
            ]);
        } else {
            $attributes = ServicePageOverrides::filterAutomatedAttributes($page, [
                'title' => $title,
                'slug' => $cmsSlug,
                'is_active' => true,
                'page_category' => PageCategory::Location,
                'meta_title' => $this->locationMetaTitle($service, $pin),
                'meta_description' => $this->localMetaDescription($service, $pin),
                'h1' => $title,
                'heading_h2' => $h2 !== null ? [$h2] : null,
                'heading_h3' => $h3 !== null ? [$h3] : null,
                'canonical_url' => $canonical,
            ]);

            if ($attributes !== []) {
                $page->update($attributes);
            }
        }

        if (filled($description) && ! ServicePageOverrides::aeoOverride($page)) {
            $page->forceFill(['aeo_answer' => $description])->saveQuietly();
        }

        $mapping = ServiceLocationPage::query()->updateOrCreate(
            [
                'service_id' => $service->id,
                'pincode_id' => $pin->id,
            ],
            [
                'page_id' => $page->id,
                'slug' => $cmsSlug,
                'location_slug' => $locationSlug,
                'city_slug' => $citySlug,
            ]
        );

        $mapping->loadMissing(['page', 'pincode', 'service']);
        $locationGraph = $this->schemaGenerator->buildLocationGraph($service, $pin, $mapping);
        $schemaAttributes = ['schema_json' => $locationGraph, 'schema_type' => 'LocationServiceGraph'];

        if (ServicePageOverrides::geoOverride($page)) {
            unset($schemaAttributes['schema_json'], $schemaAttributes['schema_type']);
        }

        $aeoAttributes = ServicePageOverrides::filterAutomatedAttributes($page, [
            'aeo_question' => $pin->locationFaqs->first()?->question,
            'aeo_answer' => $pin->locationFaqs->first()?->answer,
        ]);

        $page->forceFill(array_merge($schemaAttributes, $aeoAttributes))->saveQuietly();

        $this->masterPageSync->pushToLocationPage($service, $pin, $page->fresh(), $intro);
        $this->categoryResolver->applyToPage($page);
        $this->qualityScorer->persist($service, $pin, $mapping->fresh(['page']));

        return $page->fresh();
    }

    /**
     * Minimal location page create for bulk matrix reconcile (schema/blocks enriched on --refresh).
     */
    private function provisionOneBulkFast(Service $service, PinCode $pin): Page
    {
        $cmsSlug = $this->cmsPageSlug($service, $pin);
        $locationSlug = $this->locationSlug($service, $pin);
        $citySlug = $this->urlBuilder->citySlugForPin($pin);
        $title = $this->locationTitle($service, $pin);
        $canonical = $this->urlBuilder->locationUrlForPin($service, $pin);
        $content = (string) config('services_master.location_page_content', ServiceDetailPageProvisioner::DEFAULT_PAGE_CONTENT);

        $page = Page::withoutEvents(function () use ($title, $cmsSlug, $content, $canonical): Page {
            return Page::query()->create([
                'uuid' => (string) Str::uuid(),
                'title' => $title,
                'slug' => $cmsSlug,
                'content' => $content,
                'is_active' => true,
                'layout_mode' => PageLayoutMode::Canvas,
                'page_category' => PageCategory::Location,
                'page_source' => 'generated',
                'registry_owner' => 'operations_location_matrix',
                'meta_title' => mb_substr($title, 0, 255),
                'h1' => $title,
                'canonical_url' => $canonical,
            ]);
        });

        ServiceLocationPage::query()->updateOrCreate(
            [
                'service_id' => $service->id,
                'pincode_id' => $pin->id,
            ],
            [
                'page_id' => $page->id,
                'slug' => $cmsSlug,
                'location_slug' => $locationSlug,
                'city_slug' => $citySlug,
            ]
        );

        return $page;
    }

    public function localIntro(Service $service, PinCode $pin): string
    {
        $pin->loadMissing(['nearbyAreas', 'landmarks']);
        $area = $pin->area_name ?: $pin->locality ?: $pin->city ?: $pin->pincode;
        $city = $pin->city ?: app(LocalityContextResolver::class)->primaryCity() ?: $pin->pincode;

        $fallback = filled($pin->coverage_text)
            ? trim((string) $pin->coverage_text)
            : trim($service->title.' '.__('in :area, :city delivers doctor-led home healthcare with nursing, physiotherapy, and 24×7 medical support.', [
                'area' => $area,
                'city' => $city,
            ]));

        if (! filled($pin->coverage_text)) {
            $nearby = $pin->nearbyAreas->pluck('area_name')->filter()->take(3)->implode(', ');
            if ($nearby !== '') {
                $fallback .= ' '.__('Nearby areas include :areas.', ['areas' => $nearby]);
            }
        }

        return $this->templates->localIntro($service, $pin, $fallback);
    }

    public function deleteAllForService(Service $service): void
    {
        $this->bulkDeleteLocationArtifactsForServiceIds([$service->id]);
    }

    /**
     * @param  list<int>  $serviceIds
     */
    public function bulkDeleteLocationArtifactsForServiceIds(array $serviceIds): int
    {
        if ($serviceIds === []) {
            return 0;
        }

        $mappings = ServiceLocationPage::query()
            ->whereIn('service_id', $serviceIds)
            ->with(['service:id,service_code', 'pincode:id,pincode'])
            ->get();

        if ($mappings->isEmpty()) {
            return 0;
        }

        $pageIds = $mappings->pluck('page_id')->filter()->unique()->values()->all();

        $registryKeys = $mappings
            ->map(fn (ServiceLocationPage $mapping): ?string => $mapping->service && $mapping->pincode
                ? 'location:'.$mapping->service->service_code.':'.$mapping->pincode->pincode
                : null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($registryKeys !== []) {
            PageRegistry::query()->whereIn('registry_key', $registryKeys)->delete();
        }

        if ($pageIds !== []) {
            PageRegistry::query()->whereIn('page_id', $pageIds)->delete();
            Page::query()->whereIn('id', $pageIds)->delete();
        }

        ServiceLocationPage::query()->whereIn('service_id', $serviceIds)->delete();

        return $mappings->count();
    }

    public function locationMetaTitle(Service $service, PinCode $pin): string
    {
        $service->loadMissing(['seo']);
        $area = $pin->area_name ?: $pin->locality ?: $pin->pincode;
        $base = $service->seo?->meta_title ?: $service->title;
        $fallback = mb_substr($base.' — '.$area, 0, 255);

        return $this->templates->localMetaTitle($service, $pin, $fallback);
    }

    public function localMetaDescription(Service $service, PinCode $pin): ?string
    {
        $service->loadMissing(['seo']);

        if (filled($pin->meta_description)) {
            $fallback = mb_substr(trim((string) $pin->meta_description).' '.mb_substr($this->localIntro($service, $pin), 0, 120), 0, 320);
        } else {
            $area = $pin->area_name ?: $pin->locality ?: $pin->city;
            $base = $service->seo?->meta_description ?: $service->short_summary;
            $fallback = filled($base)
                ? mb_substr(trim((string) $base).' '.__('Available in :area (:pin).', [
                    'area' => $area,
                    'pin' => $pin->pincode,
                ]), 0, 320)
                : mb_substr($this->localIntro($service, $pin), 0, 320);
        }

        return $this->templates->localMetaDescription($service, $pin, $fallback);
    }

    private function uniqueSlug(string $base, ?int $exceptPageId = null): string
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
}
