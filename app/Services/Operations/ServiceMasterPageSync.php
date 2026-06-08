<?php

namespace App\Services\Operations;

use App\Enums\PageCategory;
use App\Models\Page;
use App\Models\PageFaq;
use App\Models\PinCode;
use App\Models\Service;
use App\Support\ServicePageOverrides;

/**
 * Push Operations → Service master fields onto owned Site Architect pages.
 */
class ServiceMasterPageSync
{
    public function __construct(
        private readonly PageCategoryResolver $categoryResolver,
    ) {}

    public function pushToPage(Service $service, Page $page, bool $forceEmptyOnly = false): void
    {
        if ($page->page_category === PageCategory::Location) {
            return;
        }

        $service->loadMissing(['seo', 'faqs', 'schema', 'pincodes']);

        $changed = false;

        if (! ServicePageOverrides::seoOverride($page)) {
            $changed = $this->applySeoFields($service, $page, $forceEmptyOnly) || $changed;
        } elseif ($page->schema_json === null) {
            $changed = $this->repairEmptySchemaOnly($service, $page) || $changed;
        }

        if (! ServicePageOverrides::aeoOverride($page)) {
            $changed = $this->applyAeoFields($service, $page, $forceEmptyOnly) || $changed;
        }

        if (! ServicePageOverrides::geoOverride($page)) {
            $changed = $this->applyGeoFields($service, $page, $forceEmptyOnly) || $changed;
        }

        if ($changed) {
            $page->save();
        }

        $this->syncPageFaqsFromService($service, $page, $forceEmptyOnly);
        $this->categoryResolver->applyToPage($page);
    }

    public function pushToLocationPage(Service $service, PinCode $pin, Page $page, string $intro): void
    {
        $service->loadMissing(['seo']);
        $pin->loadMissing(['locationFaqs']);

        $area = $pin->area_name ?: $pin->locality ?: $pin->city ?: $pin->pincode;
        $title = app(ServiceLocationPageProvisioner::class)->locationTitle($service, $pin);

        $locationAttributes = ServicePageOverrides::filterAutomatedAttributes($page, [
            'meta_title' => mb_substr(($service->seo?->meta_title ?: $service->title).' — '.$area, 0, 255),
            'meta_description' => app(ServiceLocationPageProvisioner::class)->localMetaDescription($service, $pin)
                ?? mb_substr($intro, 0, 320),
            'h1' => $title,
        ]);

        if ($locationAttributes !== []) {
            $page->forceFill($locationAttributes);
        }

        if (! ServicePageOverrides::aeoOverride($page)) {
            $firstFaq = $pin->locationFaqs->first();
            if ($firstFaq !== null) {
                $page->aeo_question = $firstFaq->question;
                $page->aeo_answer = $firstFaq->answer;
            }
        }

        if ($page->isDirty()) {
            $page->save();
        }

        $this->syncPageFaqsFromPin($pin, $page);
        $this->categoryResolver->applyToPage($page);
    }

    private function syncPageFaqsFromPin(PinCode $pin, Page $page): void
    {
        if (ServicePageOverrides::aeoOverride($page)) {
            return;
        }

        $page->faqs()->delete();

        foreach ($pin->locationFaqs->values() as $index => $faq) {
            if (! filled($faq->question) || ! filled($faq->answer)) {
                continue;
            }
            PageFaq::query()->create([
                'page_id' => $page->id,
                'sort_order' => $index,
                'question' => $faq->question,
                'answer' => $faq->answer,
            ]);
        }
    }

    private function applySeoFields(Service $service, Page $page, bool $emptyOnly): bool
    {
        $seo = $service->seo;
        $changed = false;

        $pairs = [
            'meta_title' => $seo?->meta_title ?: $service->title,
            'meta_description' => $seo?->meta_description,
            'h1' => $seo?->h1 ?: $service->title,
            'canonical_url' => $seo?->canonical_url ?: $service->publicUrl(),
            'og_title' => $seo?->og_title ?: $seo?->meta_title,
            'og_description' => $seo?->og_description ?: $seo?->meta_description,
            'og_image' => $seo?->og_image ?: $service->featured_image,
            'twitter_card' => $seo?->twitter_card ?: 'summary_large_image',
        ];

        if (ServicePageOverrides::titleOverride($page)) {
            unset($pairs['h1']);
        }

        foreach ($pairs as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if ($emptyOnly && filled($page->{$field})) {
                continue;
            }
            if ($page->{$field} !== $value) {
                $page->{$field} = $value;
                $changed = true;
            }
        }

        $focus = is_array($seo?->focus_keywords) ? $seo->focus_keywords : [];
        if ($focus !== [] && (! $emptyOnly || $page->focus_keywords === null)) {
            $page->focus_keywords = $focus;
            $page->keywords = implode(', ', $focus);
            $changed = true;
        }

        $robots = ($seo?->robots_index ?? true) ? 'index,follow' : 'noindex,nofollow';
        if (! $emptyOnly || ! filled($page->robots_meta)) {
            $page->robots_meta = $robots;
            $changed = true;
        }

        if ($service->schema?->schema_json !== null && $page->schema_json === null) {
            $page->schema_json = $service->schema->schema_json;
            $page->schema_type = $service->schema->schema_type ?: 'ServiceGraph';
            $changed = true;
        }

        return $changed;
    }

    private function repairEmptySchemaOnly(Service $service, Page $page): bool
    {
        if ($page->schema_json !== null || $service->schema?->schema_json === null) {
            return false;
        }

        $page->schema_json = $service->schema->schema_json;
        $page->schema_type = $service->schema->schema_type ?: 'ServiceGraph';

        return true;
    }

    private function applyAeoFields(Service $service, Page $page, bool $emptyOnly): bool
    {
        $changed = false;
        $seo = $service->seo;

        foreach ([
            'ai_context' => $seo?->ai_context,
            'search_intent' => $seo?->search_intent,
        ] as $field => $value) {
            if (! filled($value)) {
                continue;
            }
            if ($emptyOnly && filled($page->{$field})) {
                continue;
            }
            $page->{$field} = $value;
            $changed = true;
        }

        $firstFaq = $service->faqs->first();
        if ($firstFaq !== null && filled($firstFaq->question)) {
            if (! $emptyOnly || ! filled($page->aeo_question)) {
                $page->aeo_question = $firstFaq->question;
                $page->aeo_answer = $firstFaq->answer;
                $changed = true;
            }
        }

        return $changed;
    }

    private function applyGeoFields(Service $service, Page $page, bool $emptyOnly): bool
    {
        if (! filled($service->ai_summary)) {
            return false;
        }

        $tags = is_array($service->seo?->entity_tags) ? $service->seo->entity_tags : [];
        if ($tags !== [] && (! $emptyOnly || $page->entity_tags === null)) {
            $page->entity_tags = $tags;
        }

        return false;
    }

    private function syncPageFaqsFromService(Service $service, Page $page, bool $emptyOnly): void
    {
        if ($emptyOnly && $page->faqs()->exists()) {
            return;
        }

        if (ServicePageOverrides::aeoOverride($page)) {
            return;
        }

        $page->faqs()->delete();

        foreach ($service->faqs->values() as $index => $faq) {
            if (! filled($faq->question) || ! filled($faq->answer)) {
                continue;
            }
            PageFaq::query()->create([
                'page_id' => $page->id,
                'sort_order' => $index,
                'question' => $faq->question,
                'answer' => $faq->answer,
            ]);
        }
    }
}
