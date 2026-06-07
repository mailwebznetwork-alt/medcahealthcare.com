<?php

namespace App\Services\Operations;

use App\Enums\PageCategory;
use App\Enums\PageLayoutMode;
use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Seo\LocalityContextResolver;

class ServiceLocationPageProvisioner
{
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
    public function syncAllForService(Service $service): array
    {
        $service->loadMissing(['pincodes', 'seo', 'faqs', 'schema', 'locationPages.pincode']);

        app(ServiceDetailPageProvisioner::class)->syncStarterBlocks();

        $created = 0;
        $updated = 0;
        $activePinIds = $service->pincodes->pluck('id')->all();

        foreach ($service->pincodes as $pin) {
            $existed = ServiceLocationPage::query()
                ->where('service_id', $service->id)
                ->where('pincode_id', $pin->id)
                ->exists();

            $this->provisionOne($service, $pin);
            if ($existed) {
                $updated++;
            } else {
                $created++;
            }
        }

        $removed = 0;
        $orphans = ServiceLocationPage::query()
            ->where('service_id', $service->id)
            ->when($activePinIds !== [], fn ($q) => $q->whereNotIn('pincode_id', $activePinIds))
            ->with('page')
            ->get();

        foreach ($orphans as $row) {
            $row->page?->delete();
            $row->delete();
            $removed++;
        }

        return compact('created', 'updated', 'removed');
    }

    public function provisionOne(Service $service, PinCode $pin): Page
    {
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
        $pivotActive = ServiceLocationMatrixPivot::isActive($service, $pin);
        $pageActive = $pivotActive
            && $service->is_active
            && $service->publish_status === PublishStatus::Published;

        if ($page === null) {
            $page = Page::query()->create([
                'title' => $title,
                'slug' => $cmsSlug,
                'content' => $content,
                'is_active' => $pageActive,
                'layout_mode' => PageLayoutMode::Canvas,
                'page_category' => PageCategory::Location,
                'page_source' => 'generated',
                'registry_owner' => 'operations_location_matrix',
                'meta_title' => $this->localMetaTitle($service, $pin),
                'meta_description' => $this->localMetaDescription($service, $pin),
                'h1' => $title,
                'heading_h2' => $h2 !== null ? [$h2] : null,
                'heading_h3' => $h3 !== null ? [$h3] : null,
                'canonical_url' => $canonical,
            ]);
        } else {
            $page->update([
                'title' => $title,
                'slug' => $cmsSlug,
                'is_active' => $pageActive,
                'page_category' => PageCategory::Location,
                'meta_title' => $this->localMetaTitle($service, $pin),
                'meta_description' => $this->localMetaDescription($service, $pin),
                'h1' => $title,
                'heading_h2' => $h2 !== null ? [$h2] : null,
                'heading_h3' => $h3 !== null ? [$h3] : null,
                'canonical_url' => $canonical,
            ]);
        }

        if (filled($description)) {
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
        $page->forceFill([
            'schema_json' => $locationGraph,
            'schema_type' => 'LocationServiceGraph',
            'aeo_question' => $pin->locationFaqs->first()?->question,
            'aeo_answer' => $pin->locationFaqs->first()?->answer,
        ])->saveQuietly();

        $this->masterPageSync->pushToLocationPage($service, $pin, $page->fresh(), $intro);
        $this->categoryResolver->applyToPage($page);
        $this->qualityScorer->persist($service, $pin, $mapping->fresh(['page']));

        return $page->fresh();
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
        ServiceLocationPage::query()
            ->where('service_id', $service->id)
            ->with('page')
            ->each(function (ServiceLocationPage $row): void {
                $row->page?->delete();
                $row->delete();
            });
    }

    private function localMetaTitle(Service $service, PinCode $pin): string
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
