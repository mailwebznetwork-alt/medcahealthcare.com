<?php

namespace App\Services\Operations;

use App\Enums\PageCategory;
use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use App\Models\PageFaq;
use App\Models\ServiceCategory;
use App\Services\Discovery\Expansion\AeoExpansionEngine;
use App\Services\Discovery\Expansion\AiDiscoverabilityEngine;
use App\Services\Discovery\Expansion\GeoExpansionEngine;
use App\Services\Discovery\Expansion\SchemaExpansionEngine;
use App\Services\Discovery\Expansion\SeoExpansionEngine;
use App\Services\Discovery\RelatedContentEngine;
use App\Services\Governance\UniversalPageRegistry;

class CategoryPageProvisioner
{
    public function __construct(
        private readonly PageCategoryResolver $categoryResolver,
        private readonly SeoExpansionEngine $seoExpansion,
        private readonly AeoExpansionEngine $aeoExpansion,
        private readonly GeoExpansionEngine $geoExpansion,
        private readonly SchemaExpansionEngine $schemaExpansion,
        private readonly AiDiscoverabilityEngine $aiDiscoverability,
        private readonly RelatedContentEngine $relatedContent,
        private readonly UniversalPageRegistry $pageRegistry,
    ) {}

    public static function categoryCodeFromPageSlug(string $slug): ?string
    {
        $pattern = (string) config('phase2_discovery.category_page_slug_pattern', 'category-{code}');
        $prefix = str_replace('{code}', '', $pattern);

        if ($prefix === '' || ! str_starts_with($slug, $prefix)) {
            return null;
        }

        $code = substr($slug, strlen($prefix));

        return $code !== '' ? $code : null;
    }

    public function suggestedSlug(ServiceCategory $category): string
    {
        $pattern = (string) config('phase2_discovery.category_page_slug_pattern', 'category-{code}');

        return str_replace('{code}', $category->publicSlug(), $pattern);
    }

    public function syncFromCategory(ServiceCategory $category): Page
    {
        $category->loadMissing(['seo', 'faqs', 'schema']);

        $this->syncStarterBlocks();

        $page = $this->findOwnedPage($category);

        if ($page === null) {
            $page = Page::query()->create([
                'title' => $category->name,
                'slug' => $this->uniqueSlug($this->suggestedSlug($category)),
                'content' => (string) config('phase2_discovery.category_page_content'),
                'is_active' => $category->is_active && $category->isListedPublicly(),
                'layout_mode' => PageLayoutMode::Canvas,
                'page_category' => PageCategory::Category,
                'page_source' => 'generated',
                'registry_owner' => 'operations_category',
            ]);
        } else {
            $page->update([
                'title' => $category->name,
                'slug' => $this->uniqueSlug($this->suggestedSlug($category), $page->id),
                'is_active' => $category->is_active && $category->isListedPublicly(),
                'page_category' => PageCategory::Category,
            ]);
        }

        if ($category->page_id !== $page->id) {
            $category->forceFill(['page_id' => $page->id])->saveQuietly();
        }

        $seo = $this->seoExpansion->forCategoryPage($category, $page);
        $aeo = $this->aeoExpansion->forCategoryPage($category, $page);
        $schema = $this->schemaExpansion->forCategory($category);
        $ai = $this->aiDiscoverability->scoreCategory($category);

        $page->forceFill(array_merge($seo, $aeo, [
            'schema_json' => $schema,
            'schema_type' => 'CategoryDiscoveryGraph',
            'entity_tags' => $category->seo?->entity_tags ?: $this->geoExpansion->signalsForCategory($category),
            'keywords' => is_array($category->seo?->focus_keywords)
                ? implode(', ', $category->seo->focus_keywords)
                : null,
        ]))->saveQuietly();

        $this->syncPageFaqs($category, $page);
        $this->categoryResolver->applyToPage($page);
        $this->relatedContent->persistCategory($category);
        $this->pageRegistry->upsertCategoryEntry($category->fresh());

        if ($category->seo !== null) {
            $category->seo->forceFill([
                'ai_discovery_score' => $ai['score'],
            ])->saveQuietly();
        }

        return $page->fresh();
    }

    private function findOwnedPage(ServiceCategory $category): ?Page
    {
        if ($category->page_id !== null) {
            $linked = Page::query()->find($category->page_id);
            if ($linked !== null) {
                return $linked;
            }
        }

        return Page::query()->where('slug', $this->suggestedSlug($category))->first();
    }

    private function syncPageFaqs(ServiceCategory $category, Page $page): void
    {
        $category->loadMissing('faqs');
        if ($category->faqs->isEmpty()) {
            return;
        }

        PageFaq::query()->where('page_id', $page->id)->delete();

        foreach ($category->faqs as $i => $faq) {
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
            ['slug' => 'category-discovery-hero', 'view' => 'blocks.categories.category-discovery-hero'],
            ['slug' => 'category-services-list', 'view' => 'blocks.categories.category-services-list'],
            ['slug' => 'category-related', 'view' => 'blocks.categories.category-related'],
            ['slug' => 'category-areas-served', 'view' => 'blocks.categories.category-areas-served'],
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
