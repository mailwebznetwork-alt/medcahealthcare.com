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
use App\Services\Import\ImportSideEffectsGate;
use App\Support\ServicePageOverrides;

class SubServicePageProvisioner
{
    public const string DEFAULT_PAGE_CONTENT = "{{block:sub-service-detail-hero}}\n{{block:sub-service-detail-body}}";

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

    public function resolveOwnedPage(SubService $sub): ?Page
    {
        return $this->findOwnedPage($sub);
    }

    /**
     * Link an existing generated page without running the full sync pipeline.
     */
    public function relinkOwnedPage(SubService $sub): ?Page
    {
        $page = $this->findOwnedPage($sub);

        if ($page === null) {
            return null;
        }

        if ($sub->page_id !== $page->id) {
            $sub->forceFill(['page_id' => $page->id])->saveQuietly();
        }

        return $page;
    }

    public function syncFromSubService(SubService $sub): Page
    {
        return app(ImportSideEffectsGate::class)->run(function () use ($sub): Page {
            $sub->loadMissing(['seo', 'faqs', 'schema', 'service']);

            if (! ServiceGeneratedPageEligibility::subServiceMayHavePages($sub)) {
                $this->deleteOwnedPage($sub);

                throw new \RuntimeException("Sub-service is not eligible for generated pages: {$sub->sub_service_code}");
            }

            $this->syncStarterBlocks();

            $page = $this->findOwnedPage($sub);

            if ($page === null) {
                $page = Page::query()->create([
                    'title' => $sub->title,
                    'slug' => $this->uniqueSlug($this->suggestedSlug($sub)),
                    'content' => (string) config('phase2_discovery.sub_service_page_content'),
                    'is_active' => true,
                    'layout_mode' => PageLayoutMode::Canvas,
                    'page_category' => PageCategory::SubService,
                    'page_source' => 'generated',
                    'registry_owner' => 'operations_sub_service',
                ]);
            } else {
                $attributes = ServicePageOverrides::filterAutomatedAttributes($page, [
                    'title' => $sub->title,
                    'slug' => $this->uniqueSlug($this->suggestedSlug($sub), $page->id),
                    'is_active' => true,
                    'page_category' => PageCategory::SubService,
                ]);

                if ($attributes !== []) {
                    $page->forceFill($attributes)->saveQuietly();
                }

                if (! ServicePageOverrides::contentOverride($page)) {
                    $content = trim((string) $page->content);
                    if ($content === '' || ! str_contains($content, 'sub-service-detail-hero')) {
                        $page->forceFill(['content' => self::DEFAULT_PAGE_CONTENT])->saveQuietly();
                    } elseif (! str_contains($content, 'sub-service-detail-body')) {
                        $page->forceFill(['content' => $this->injectDetailBodyBlock($content)])->saveQuietly();
                    }
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

            if ($sub->seo !== null) {
                $sub->seo->forceFill([
                    'ai_discovery_score' => $ai['score'],
                ])->saveQuietly();
            }

            return $page->fresh();
        });
    }

    public function deleteOwnedPage(SubService $sub): void
    {
        $page = $this->findOwnedPage($sub);

        if ($page !== null) {
            app(\App\Services\Governance\DownstreamArtifactPurger::class)->purgeForDeletedSubService($sub);
            $page->delete();
        }

        if ($sub->page_id !== null) {
            $sub->forceFill(['page_id' => null])->saveQuietly();
        }
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
            ['slug' => 'sub-service-detail-body', 'view' => 'blocks.sub-services.sub-service-detail-body'],
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

    private function injectDetailBodyBlock(string $content): string
    {
        if (str_contains($content, '{{block:sub-service-detail-body}}')) {
            return $content;
        }

        if (str_contains($content, '{{block:sub-service-detail-hero}}')) {
            return str_replace(
                '{{block:sub-service-detail-hero}}',
                "{{block:sub-service-detail-hero}}\n{{block:sub-service-detail-body}}",
                $content
            );
        }

        return "{{block:sub-service-detail-body}}\n".$content;
    }
}
