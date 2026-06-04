<?php

namespace App\Services\Pages;

use App\Models\Page;

/**
 * Non-destructive inserts for marketing page block tokens (preserves custom order when possible).
 */
class MarketingPageBlockPatcher
{
    /**
     * @return array{home: bool, locations: bool}
     */
    public function ensureRequiredNearYouBlocks(): array
    {
        return [
            'home' => $this->ensureNearYouBlockOnPage(
                pageSlug: 'home',
                blockSlug: 'near-you-home',
                insertAfter: 'services-overview-home',
            ),
            'locations' => $this->ensureNearYouBlockOnPage(
                pageSlug: 'locations',
                blockSlug: 'near-you-locations',
                insertAfter: 'hero-locations',
            ),
        ];
    }

    public function ensureHomeNearYouBlock(): bool
    {
        return $this->ensureNearYouBlockOnPage('home', 'near-you-home', 'services-overview-home');
    }

    public function pageHasNearYouBlock(?Page $page): bool
    {
        if ($page === null) {
            return false;
        }

        return (bool) preg_match('/\{\{\s*block\s*:\s*near-you[\w-]*\s*\}\}/', (string) $page->content);
    }

    private function ensureNearYouBlockOnPage(string $pageSlug, string $blockSlug, string $insertAfter): bool
    {
        $page = Page::query()->where('slug', $pageSlug)->first();
        if ($page === null) {
            return false;
        }

        if (str_contains((string) $page->content, $blockSlug)) {
            return false;
        }

        $parts = Page::parseContentTokens($page->content);
        $insert = ['type' => 'block', 'slug' => $blockSlug];
        $inserted = false;

        foreach ($parts as $index => $part) {
            if (($part['slug'] ?? '') === $insertAfter) {
                array_splice($parts, $index + 1, 0, [$insert]);
                $inserted = true;

                break;
            }
        }

        if (! $inserted) {
            $parts[] = $insert;
        }

        $page->forceFill([
            'content' => Page::buildContentFromParts($parts),
        ])->save();

        return true;
    }
}
