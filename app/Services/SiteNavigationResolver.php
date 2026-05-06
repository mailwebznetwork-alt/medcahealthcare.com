<?php

namespace App\Services;

use App\Models\Page;
use App\Models\SiteNavigationItem;
use Illuminate\Support\Facades\Schema;

class SiteNavigationResolver
{
    /**
     * @return list<array{label: string, href: string}>
     */
    public function headerLinks(): array
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return $this->defaultHeaderLinks();
        }

        $rows = SiteNavigationItem::query()
            ->where('zone', SiteNavigationItem::ZONE_HEADER)
            ->orderBy('sort_order')
            ->with('page')
            ->get();

        $links = [];
        foreach ($rows as $row) {
            $page = $row->page;
            if ($page === null || ! $page->is_active) {
                continue;
            }
            $links[] = [
                'label' => $this->resolveNavLabel($row, $page),
                'href' => route('pages.public', ['slug' => $page->slug]),
            ];
        }

        if ($links === []) {
            return $this->defaultHeaderLinks();
        }

        return $links;
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    public function footerLinks(): array
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return [];
        }

        $rows = SiteNavigationItem::query()
            ->where('zone', SiteNavigationItem::ZONE_FOOTER)
            ->orderBy('sort_order')
            ->with('page')
            ->get();

        $links = [];
        foreach ($rows as $row) {
            $page = $row->page;
            if ($page === null || ! $page->is_active) {
                continue;
            }
            $links[] = [
                'label' => $this->resolveNavLabel($row, $page),
                'href' => route('pages.public', ['slug' => $page->slug]),
            ];
        }

        return $links;
    }

    protected function resolveNavLabel(SiteNavigationItem $row, Page $page): string
    {
        $custom = $row->custom_label ?? null;

        if ($custom !== null && trim((string) $custom) !== '') {
            return trim((string) $custom);
        }

        return $page->title;
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    protected function defaultHeaderLinks(): array
    {
        return [
            ['label' => __('Home'), 'href' => url('/')],
            ['label' => __('About Us'), 'href' => url('/#about')],
            ['label' => __('Services'), 'href' => url('/#services')],
            ['label' => __('Locations'), 'href' => url('/#locations')],
            ['label' => __('Careers'), 'href' => route('careers.index')],
            ['label' => __('Contact Us'), 'href' => url('/#contact')],
        ];
    }
}
