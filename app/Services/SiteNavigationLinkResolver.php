<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SiteNavigationItem;
use App\Models\SubService;

class SiteNavigationLinkResolver
{
    public function label(SiteNavigationItem $item): string
    {
        if (filled($item->title)) {
            return (string) $item->title;
        }

        if (filled($item->custom_label)) {
            return (string) $item->custom_label;
        }

        return match ($item->item_type) {
            SiteNavigationItem::TYPE_PAGE => (string) ($item->page?->title ?? __('Page')),
            SiteNavigationItem::TYPE_CATEGORY => (string) ($item->serviceCategory?->name ?? __('Category')),
            SiteNavigationItem::TYPE_SERVICE => (string) ($item->service?->title ?? __('Service')),
            SiteNavigationItem::TYPE_SUB_SERVICE => (string) ($item->subService?->title ?? __('Sub-service')),
            SiteNavigationItem::TYPE_URL => (string) ($item->title ?: __('Link')),
            SiteNavigationItem::TYPE_GROUP => (string) ($item->title ?: __('Menu')),
            default => __('Link'),
        };
    }

    public function href(SiteNavigationItem $item): ?string
    {
        return match ($item->item_type) {
            SiteNavigationItem::TYPE_PAGE => $item->page ? $this->pageHref($item->page) : null,
            SiteNavigationItem::TYPE_CATEGORY => $item->serviceCategory?->publicUrl(),
            SiteNavigationItem::TYPE_SERVICE => $item->service?->publicUrl(),
            SiteNavigationItem::TYPE_SUB_SERVICE => $item->subService?->publicUrl(),
            SiteNavigationItem::TYPE_URL => filled($item->custom_url) ? (string) $item->custom_url : null,
            SiteNavigationItem::TYPE_GROUP => null,
            default => null,
        };
    }

    public function pageHref(Page $page): string
    {
        if ($page->slug === 'home') {
            return url('/');
        }

        return $page->publicUrl();
    }

    public function isNavigable(SiteNavigationItem $item): bool
    {
        if ($item->item_type === SiteNavigationItem::TYPE_GROUP) {
            return false;
        }

        return $this->href($item) !== null;
    }

    /**
     * @return array{label: string, href: string|null, children: list<array<string, mixed>>}
     */
    public function toNavNode(SiteNavigationItem $item): array
    {
        $children = $item->relationLoaded('children')
            ? $item->children->map(fn (SiteNavigationItem $child): array => $this->toNavNode($child))->values()->all()
            : [];

        return [
            'label' => $this->label($item),
            'href' => $this->href($item),
            'children' => $children,
        ];
    }
}
