<?php

namespace App\Services\Operations;

use App\Enums\PageCategory;
use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use App\Models\PageFaq;
use App\Models\SubService;
use App\Services\Discovery\Expansion\AeoExpansionEngine;
use App\Services\Discovery\Expansion\AiDiscoverabilityEngine;
use App\Services\Discovery\Expansion\SchemaExpansionEngine;
use App\Services\Discovery\Expansion\SeoExpansionEngine;
use App\Services\Discovery\RelatedContentEngine;
use App\Services\Governance\UniversalPageRegistry;
use App\Support\ServicePageOverrides;

class SubServicePageProvisioner
{
    public function __construct(
        private readonly PageCategoryResolver $categoryResolver,
        private readonly SeoExpansionEngine $seoExpansion,
        private readonly AeoExpansionEngine $aeoExpansion,
        private readonly SchemaExpansionEngine $schemaExpansion,
        private readonly AiDiscoverabilityEngine $aiDiscoverability,
        private readonly RelatedContentEngine $relatedContent,
        private readonly UniversalPageRegistry $pageRegistry,
    ) {}

    public static function subCodeFromPageSlug(string $slug): ?string
    {
        if (! preg_match('/-sub-([a-z0-9-]+)$/i', $slug, $m)) {
            return null;
        }

        return $m[1] ?? null;
    }

    public function suggestedSlug(SubService $sub): string
    {
        $sub->loadMissing('service');
        $pattern = (string) config('phase2_discovery.sub_service_page_slug_pattern', 'service-{code}-sub-{sub}');

        return str_replace(
            ['{code}', '{sub}'],
            [$sub->service?->service_code ?? 'unknown', $sub->sub_service_code],
            $pattern
        );
    }

    public function syncFromSubService(SubService $sub): Page
    {
        $sub->loadMissing(['seo', 'faqs', 'schema', 'service']);

        $this->syncStarterBlocks();

        $page = $this->findOwnedPage($sub);

        if ($page === null) {
            $page = Page::query()->create([
                'title' => $sub->title,
                'slug' => $this->uniqueSlug($this->suggestedSlug($sub)),
                'content' => (string) config('phase2_discovery.sub_service_page_content'),
                'is_active' => $sub->is_active && $sub->isListedPublicly(),
                'layout_mode' => PageLayoutMode::Canvas,
                'page_category' => PageCategory::SubService,
                'page_source' => 'generated',
                'registry_owner' => 'operations_sub_service',
            ]);
        } else {
            $attributes = ServicePageOverrides::filterAutomatedAttributes($page, [
                'title' => $sub->title,
                'slug' => $this->uniqueSlug($this->suggestedSlug($sub), $page->id),
                'is_active' => $sub->is_active && $sub->isListedPublicly(),
                'page_category' => PageCategory::SubService,
            ]);

            if ($attributes !== []) {
                $page->update($attributes);
            }
        }

        if ($sub->page_id !== $page->id) {
            $sub->forceFill(['page_id' => $page->id])->saveQuietly();
        }

        $seo = $this->seoExpansion->forSubServicePage($sub, $page);
        $aeo = $this->aeoExpansion->forSubServicePage($sub, $page);
        $schema = $this->schemaExpansion->forSubService($sub);
        $ai = $this->aiDiscoverability->scoreSubService($sub);

        $expansion = ServicePageOverrides::filterAutomatedAttributes($page, array_merge($seo, $aeo, []));

        if ($page->schema_json === null) {
            $expansion['schema_json'] = $schema;
            $expansion['schema_type'] = 'SubServiceGraph';
        }

        if ($expansion !== []) {
            $page->forceFill($expansion)->saveQuietly();
        }

        $this->syncPageFaqs($sub, $page);
        $this->categoryResolver->applyToPage($page);
        $this->relatedContent->persistSubService($sub);
        $this->pageRegistry->upsertSubServiceEntry($sub->fresh());

        return $page->fresh();
    }

    public function deleteOwnedPage(SubService $sub): void
    {
        $page = $this->findOwnedPage($sub);

        if ($page === null) {
            return;
        }

        app(\App\Services\Governance\DownstreamArtifactPurger::class)->purgeForDeletedSubService($sub);
        $page->delete();
    }

    private function findOwnedPage(SubService $sub): ?Page
    {
        if ($sub->page_id !== null) {
            $linked = Page::query()->find($sub->page_id);
            if ($linked !== null) {
                return $linked;
            }
        }

        return Page::query()->where('slug', $this->suggestedSlug($sub))->first();
    }

    private function syncPageFaqs(SubService $sub, Page $page): void
    {
        if (ServicePageOverrides::aeoOverride($page)) {
            return;
        }

        $sub->loadMissing('faqs');
        if ($sub->faqs->isEmpty()) {
            return;
        }

        PageFaq::query()->where('page_id', $page->id)->delete();

        foreach ($sub->faqs as $i => $faq) {
            PageFaq::query()->create([
                'page_id' => $page->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'sort_order' => $faq->sort_order ?: $i,
            ]);
        }
    }

    public function syncStarterBlocks(): void
    {
        $blocks = [
            ['slug' => 'sub-service-detail-hero', 'view' => 'blocks.sub-services.sub-service-detail-hero'],
            ['slug' => 'sub-service-related', 'view' => 'blocks.sub-services.sub-service-related'],
        ];

        foreach ($blocks as $block) {
            Block::query()->updateOrCreate(
                ['block_slug' => $block['slug']],
                [
                    'block_name' => str_replace('-', ' ', ucwords($block['slug'], '-')),
                    'code' => $block['view'],
                    'is_active' => true,
                    'is_managed' => true,
                ]
            );
        }
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
