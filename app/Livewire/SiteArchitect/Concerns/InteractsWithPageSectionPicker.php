<?php

namespace App\Livewire\SiteArchitect\Concerns;

use App\Models\Block;
use App\Services\SiteArchitect\PageSectionCatalog;
use App\Support\SiteArchitectNavigation;

trait InteractsWithPageSectionPicker
{
    public bool $sectionPickerOpen = false;

    public string $sectionPickerSearch = '';

    public string $sectionPickerCategory = 'all';

    public function openSectionPicker(): void
    {
        $this->sectionPickerSearch = '';
        $this->sectionPickerCategory = 'all';
        $this->sectionPickerOpen = true;
    }

    public function closeSectionPicker(): void
    {
        $this->sectionPickerOpen = false;
    }

    public function appendSection(string $slug): void
    {
        $slug = trim($slug);
        if ($slug === '' || ! Block::query()->where('block_slug', $slug)->where('is_active', true)->exists()) {
            return;
        }

        $this->contentParts[] = ['type' => 'block', 'slug' => $slug];
        $this->sectionPickerOpen = false;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    protected function sectionPickerGroups(): array
    {
        return app(PageSectionCatalog::class)->groupedForPicker(
            $this->currentPageSlugForPicker(),
            $this->sectionPickerSearch !== '' ? $this->sectionPickerSearch : null,
            $this->sectionPickerCategory !== 'all' ? $this->sectionPickerCategory : null,
        );
    }

    protected function sectionDisplayName(string $type, string $slug): string
    {
        if ($type !== 'block') {
            return ucfirst($type).' · '.app(PageSectionCatalog::class)->displayNameForSlug($slug);
        }

        return app(PageSectionCatalog::class)->displayNameForSlug($slug);
    }

    protected function canUseDeveloperBlockTools(): bool
    {
        return SiteArchitectNavigation::showsDeveloperBlockTools(auth()->user());
    }

    /**
     * Override on Pages/Blogs to pass current page slug for recommendations.
     */
    protected function currentPageSlugForPicker(): ?string
    {
        return null;
    }
}
