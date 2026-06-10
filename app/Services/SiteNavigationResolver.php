<?php

namespace App\Services;

use App\Models\Page;
use App\Models\SiteNavigationItem;
use Illuminate\Support\Facades\Schema;

class SiteNavigationResolver
{
    public function __construct(
        private readonly SiteNavigationTreeService $treeService,
        private readonly SiteNavigationCatalogMerger $catalogMerger,
    ) {}

    /**
     * Nested header menu for dropdown rendering.
     *
     * @return list<array{label: string, href: string|null, children: list<array<string, mixed>>}>
     */
    public function headerNav(): array
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return $this->defaultHeaderNav();
        }

        $tree = $this->treeService->publicNavForZone(SiteNavigationItem::ZONE_HEADER);

        if ($tree === []) {
            return $this->defaultHeaderNav();
        }

        return $this->catalogMerger->mergeCatalogUnderServices($tree, SiteNavigationItem::ZONE_HEADER);
    }

    /**
     * Nested footer menu.
     *
     * @return list<array{label: string, href: string|null, children: list<array<string, mixed>>}>
     */
    public function footerNav(): array
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return [];
        }

        return $this->treeService->publicNavForZone(SiteNavigationItem::ZONE_FOOTER);
    }

    /**
     * Flat header links (legacy consumers).
     *
     * @return list<array{label: string, href: string}>
     */
    public function headerLinks(): array
    {
        return $this->flattenNav($this->headerNav());
    }

    /**
     * Flat footer links (legacy consumers).
     *
     * @return list<array{label: string, href: string}>
     */
    public function footerLinks(): array
    {
        return $this->flattenNav($this->footerNav());
    }

    /**
     * @param  list<array{label: string, href: string|null, children?: list<array<string, mixed>>}>  $nodes
     * @return list<array{label: string, href: string}>
     */
    protected function flattenNav(array $nodes): array
    {
        $links = [];

        foreach ($nodes as $node) {
            $href = $node['href'] ?? null;
            if (is_string($href) && $href !== '') {
                $links[] = [
                    'label' => (string) ($node['label'] ?? ''),
                    'href' => $href,
                ];
            }

            $children = is_array($node['children'] ?? null) ? $node['children'] : [];
            if ($children !== []) {
                array_push($links, ...$this->flattenNav($children));
            }
        }

        return $links;
    }

    /**
     * @return list<array{label: string, href: string|null, children: list<array<string, mixed>>}>
     */
    protected function defaultHeaderNav(): array
    {
        return array_map(
            static fn (array $link): array => [
                'label' => $link['label'],
                'href' => $link['href'],
                'children' => [],
            ],
            $this->defaultHeaderLinks(),
        );
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    protected function defaultHeaderLinks(): array
    {
        /** @var array<string, string> $slugLabels */
        $slugLabels = config('public_pages.default_header_nav', []);

        if ($slugLabels !== [] && Schema::hasTable('pages')) {
            $links = [];

            foreach ($slugLabels as $slug => $label) {
                $href = $this->defaultHrefForSlug((string) $slug);
                if ($href === null) {
                    continue;
                }

                $links[] = [
                    'label' => __($label),
                    'href' => $href,
                ];
            }

            if ($links !== []) {
                return $links;
            }
        }

        return $this->staticFallbackHeaderLinks();
    }

    protected function defaultHrefForSlug(string $slug): ?string
    {
        if (Schema::hasTable('pages')) {
            $page = Page::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if ($page instanceof Page) {
                return app(SiteNavigationLinkResolver::class)->pageHref($page);
            }
        }

        return match ($slug) {
            'home' => url('/'),
            'about-us' => url('/about-us'),
            'services' => url('/services'),
            'locations' => url('/locations'),
            'careers' => url('/careers'),
            'contact' => url('/contact'),
            default => null,
        };
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    protected function staticFallbackHeaderLinks(): array
    {
        return [
            ['label' => __('Home'), 'href' => url('/')],
            ['label' => __('About Us'), 'href' => url('/about-us')],
            ['label' => __('Services'), 'href' => url('/services')],
            ['label' => __('Locations'), 'href' => url('/locations')],
            ['label' => __('Careers'), 'href' => url('/careers')],
            ['label' => __('Contact Us'), 'href' => url('/contact')],
        ];
    }
}
