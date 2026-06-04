<?php

namespace App\Services\SiteArchitect;

use App\Models\Block;
use Illuminate\Support\Str;

/**
 * Human-friendly section catalog for the page builder (slug remains internal).
 */
class PageSectionCatalog
{
    /**
     * @return list<array{
     *     slug: string,
     *     display_name: string,
     *     description: string,
     *     picker_category: string,
     *     preview_key: string,
     *     block_type: string,
     *     is_managed: bool,
     *     recommended: bool
     * }>
     */
    public function entriesForPicker(?string $pageSlug = null, ?string $search = null, ?string $categoryFilter = null): array
    {
        $recommended = $pageSlug !== null
            ? (array) (config('page_builder_sections.recommended_for_page.'.$pageSlug) ?? [])
            : [];

        $blocks = Block::query()
            ->where('is_active', true)
            ->orderBy('block_name')
            ->get(['block_slug', 'block_name', 'description', 'block_type', 'is_managed']);

        $entries = [];
        foreach ($blocks as $block) {
            $meta = $this->resolveMeta(
                (string) $block->block_slug,
                (string) $block->block_name,
                (string) ($block->description ?? ''),
                (string) ($block->block_type ?? '')
            );

            if ($categoryFilter !== null && $categoryFilter !== '' && $categoryFilter !== 'all'
                && $meta['picker_category'] !== $categoryFilter) {
                continue;
            }

            if ($search !== null && $search !== '') {
                $haystack = Str::lower($meta['display_name'].' '.$meta['description'].' '.$block->block_slug);
                if (! str_contains($haystack, Str::lower($search))) {
                    continue;
                }
            }

            $entries[] = [
                'slug' => (string) $block->block_slug,
                'display_name' => $meta['display_name'],
                'description' => $meta['description'],
                'picker_category' => $meta['picker_category'],
                'preview_key' => $meta['preview_key'],
                'block_type' => (string) ($block->block_type ?? ''),
                'is_managed' => (bool) $block->is_managed,
                'recommended' => in_array($block->block_slug, $recommended, true),
            ];
        }

        usort($entries, function (array $a, array $b): int {
            if ($a['recommended'] !== $b['recommended']) {
                return $a['recommended'] ? -1 : 1;
            }

            $cat = strcmp($a['picker_category'], $b['picker_category']);
            if ($cat !== 0) {
                return $cat;
            }

            return strcmp($a['display_name'], $b['display_name']);
        });

        return $entries;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function groupedForPicker(?string $pageSlug = null, ?string $search = null, ?string $categoryFilter = null): array
    {
        $grouped = [];
        foreach ($this->entriesForPicker($pageSlug, $search, $categoryFilter) as $entry) {
            $grouped[$entry['picker_category']][] = $entry;
        }

        $order = config('page_builder_sections.picker_categories', []);
        $sorted = [];
        foreach ($order as $category) {
            if (isset($grouped[$category])) {
                $sorted[$category] = $grouped[$category];
                unset($grouped[$category]);
            }
        }
        foreach ($grouped as $category => $items) {
            $sorted[$category] = $items;
        }

        return $sorted;
    }

    public function displayNameForSlug(string $slug): string
    {
        $block = Block::query()->where('block_slug', $slug)->first(['block_slug', 'block_name', 'description', 'block_type']);

        if ($block === null) {
            return $this->humanizeSlug($slug);
        }

        return $this->resolveMeta(
            (string) $block->block_slug,
            (string) $block->block_name,
            (string) ($block->description ?? ''),
            (string) ($block->block_type ?? '')
        )['display_name'];
    }

    /**
     * @return array{display_name: string, description: string, picker_category: string, preview_key: string}
     */
    public function resolveMeta(string $slug, string $blockName, string $description, string $blockType): array
    {
        $override = config('page_builder_sections.overrides.'.$slug);
        if (is_array($override)) {
            return [
                'display_name' => (string) ($override['display_name'] ?? $this->deriveDisplayName($blockName, $slug)),
                'description' => (string) ($override['description'] ?? $description),
                'picker_category' => (string) ($override['picker_category'] ?? $this->categoryFromType($blockType, $slug)),
                'preview_key' => (string) ($override['preview_key'] ?? $this->defaultPreviewKey($blockType)),
            ];
        }

        return [
            'display_name' => $this->deriveDisplayName($blockName, $slug),
            'description' => $description !== '' ? $description : __('Reusable page section.'),
            'picker_category' => $this->categoryFromType($blockType, $slug),
            'preview_key' => $this->defaultPreviewKey($blockType),
        ];
    }

    private function deriveDisplayName(string $blockName, string $slug): string
    {
        $name = trim($blockName);
        $name = preg_replace('/^Element\s*—\s*/i', '', $name) ?? $name;
        $name = preg_replace('/^Home\s*—\s*/i', '', $name) ?? $name;
        $name = preg_replace('/^About\s*—\s*/i', '', $name) ?? $name;

        if ($name !== '') {
            return $name;
        }

        return $this->humanizeSlug($slug);
    }

    private function humanizeSlug(string $slug): string
    {
        return Str::title(str_replace('-', ' ', $slug));
    }

    private function categoryFromType(string $blockType, string $slug): string
    {
        $map = config('page_builder_sections.block_type_to_category', []);
        if ($blockType !== '' && isset($map[$blockType])) {
            return (string) $map[$blockType];
        }

        if (str_contains($slug, 'hero')) {
            return 'Hero';
        }
        if (str_contains($slug, 'faq')) {
            return 'FAQ';
        }
        if (str_contains($slug, 'cta')) {
            return 'CTA';
        }
        if (str_contains($slug, 'testimonial') || str_contains($slug, 'review')) {
            return 'Testimonials';
        }
        if (str_contains($slug, 'form') || str_contains($slug, 'contact')) {
            return 'Contact Form';
        }
        if (str_contains($slug, 'location') || str_contains($slug, 'near-you')) {
            return 'Locations';
        }
        if (str_contains($slug, 'gallery') || str_contains($slug, 'before-after')) {
            return 'Gallery';
        }
        if (str_contains($slug, 'team') || str_contains($slug, 'doctor')) {
            return 'Doctors';
        }
        if (str_contains($slug, 'stat')) {
            return 'Statistics';
        }
        if (str_contains($slug, 'process')) {
            return 'Process';
        }
        if (str_contains($slug, 'service')) {
            return 'Services';
        }

        return 'Other';
    }

    private function defaultPreviewKey(string $blockType): string
    {
        $map = config('page_builder_sections.block_type_to_category', []);
        $category = $map[$blockType] ?? 'other';

        return match ($category) {
            'Hero' => 'hero',
            'FAQ' => 'faq',
            'CTA' => 'cta',
            'Gallery' => 'gallery',
            'Testimonials', 'Reviews' => 'testimonials',
            'Contact Form' => 'form',
            'Locations' => 'map',
            'Statistics' => 'statistics',
            'Process' => 'process',
            'Doctors' => 'doctors',
            'Services' => 'services',
            default => 'default',
        };
    }
}
