<?php

namespace App\Services\Blocks;

use App\Models\Blog;
use App\Models\Block;
use App\Models\Page;
use App\Models\SectionLibraryItem;
use Illuminate\Support\Facades\Schema;

class BlockUsageFinder
{
    private const string TOKEN_PATTERN = '/\{\{\s*block\s*:\s*%s\s*\}\}/i';

    /**
     * @return list<array<string, mixed>>
     */
    public function findForSlug(string $slug): array
    {
        $usages = [];

        if (Schema::hasTable('pages')) {
            Page::query()
                ->where('content', 'like', '%{{block:'.$slug.'}}%')
                ->orWhere('content', 'like', '%{{ block:'.$slug.' }}%')
                ->get(['id', 'slug', 'title', 'is_active'])
                ->each(function (Page $page) use (&$usages): void {
                    $usages[] = [
                        'type' => 'page',
                        'slug' => $page->slug,
                        'title' => $page->title,
                        'is_active' => (bool) $page->is_active,
                        'url' => $page->publicPath(),
                    ];
                });
        }

        if (Schema::hasTable('blogs')) {
            Blog::query()
                ->where('content', 'like', '%{{block:'.$slug.'}}%')
                ->orWhere('content', 'like', '%{{ block:'.$slug.' }}%')
                ->get(['id', 'slug', 'title', 'is_published'])
                ->each(function (Blog $blog) use (&$usages): void {
                    $usages[] = [
                        'type' => 'blog',
                        'slug' => $blog->slug,
                        'title' => $blog->title,
                        'is_active' => (bool) $blog->is_published,
                        'url' => '/blog/'.$blog->slug,
                    ];
                });
        }

        if (Schema::hasTable('section_library_items')) {
            SectionLibraryItem::query()
                ->get(['slug', 'name', 'blocks_json'])
                ->each(function (SectionLibraryItem $section) use ($slug, &$usages): void {
                    if ($this->sectionReferencesBlock($section, $slug)) {
                        $usages[] = [
                            'type' => 'section',
                            'slug' => $section->slug,
                            'title' => $section->name,
                            'is_active' => true,
                            'url' => null,
                        ];
                    }
                });
        }

        $pattern = sprintf(self::TOKEN_PATTERN, preg_quote($slug, '/'));
        Block::query()
            ->where('block_slug', '!=', $slug)
            ->where(function ($query) use ($slug): void {
                $query->where('code', 'like', '%{{block:'.$slug.'}}%')
                    ->orWhere('code', 'like', '%{{ block:'.$slug.' }}%');
            })
            ->get(['block_slug', 'block_name', 'is_active'])
            ->each(function (Block $block) use (&$usages): void {
                $usages[] = [
                    'type' => 'nested_block',
                    'slug' => $block->block_slug,
                    'title' => $block->block_name,
                    'is_active' => (bool) $block->is_active,
                    'url' => null,
                ];
            });

        return $usages;
    }

    private function sectionReferencesBlock(SectionLibraryItem $section, string $slug): bool
    {
        $blocks = is_array($section->blocks_json) ? $section->blocks_json : [];

        foreach ($blocks as $entry) {
            if (is_string($entry) && $entry === $slug) {
                return true;
            }
            if (is_array($entry) && ($entry['slug'] ?? $entry['block_slug'] ?? null) === $slug) {
                return true;
            }
        }

        return false;
    }
}
