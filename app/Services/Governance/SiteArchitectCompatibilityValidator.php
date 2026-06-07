<?php

namespace App\Services\Governance;

use App\Models\Page;
use App\Models\ServiceLocationPage;

/**
 * Validates generated pages remain Site Architect compatible (not locked).
 */
class SiteArchitectCompatibilityValidator
{
    /**
     * @return array{compatible: bool, issues: list<string>, checked: int}
     */
    public function validateAll(): array
    {
        $issues = [];
        $checked = 0;

        Page::query()->orderBy('id')->each(function (Page $page) use (&$issues, &$checked): void {
            $checked++;
            $issues = array_merge($issues, $this->validatePage($page));
        });

        return [
            'compatible' => $issues === [],
            'issues' => $issues,
            'checked' => $checked,
        ];
    }

    /**
     * @return list<string>
     */
    public function validatePage(Page $page): array
    {
        $issues = [];

        if (blank($page->uuid)) {
            $issues[] = "page:{$page->slug} missing uuid";
        }

        $meta = $page->deployment_meta_json;
        if (is_array($meta) && (bool) ($meta['locked'] ?? false)) {
            $issues[] = "page:{$page->slug} is locked — Site Architect override blocked";
        }

        if (is_array($meta) && (bool) ($meta['read_only'] ?? false)) {
            $issues[] = "page:{$page->slug} is read_only";
        }

        $isGenerated = $page->page_source === 'generated'
            || ServiceLocationPage::query()->where('page_id', $page->id)->exists()
            || \App\Models\Service::query()->where('detail_page_id', $page->id)->exists();

        if ($isGenerated) {
            if (! $page->usesCanvasLayout() && $page->page_category?->value === 'service') {
                $issues[] = "page:{$page->slug} generated service page should use canvas layout";
            }
        }

        return $issues;
    }
}
