<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SiteNavigationItem;
use App\Services\Blocks\BlockTemplateSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the public marketing surface (Home, About Us, Services, Locations, Contact Us)
 * as Page rows composed of editable Block records, plus the corresponding header
 * navigation entries. Idempotent — safe to re-run; existing rows are updated.
 */
class MedcaPublicPagesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedBlocks();
            $pages = $this->seedPages();
            $this->seedHeaderNavigation($pages);
        });
    }

    private function seedBlocks(): void
    {
        app(BlockTemplateSyncService::class)->sync(
            categories: ['home', 'about', 'services', 'locations', 'contact'],
        );
    }

    /**
     * @return array<string, Page>
     */
    private function seedPages(): array
    {
        $pages = [];

        foreach ($this->pageDefinitions() as $definition) {
            $page = Page::query()->firstOrNew(['slug' => $definition['slug']]);

            if (! $page->exists) {
                $page->fill([
                    'title' => $definition['title'],
                    'content' => $this->buildContent($definition['blocks']),
                    'meta_title' => $definition['meta_title'],
                    'meta_description' => $definition['meta_description'],
                    'h1' => $definition['h1'],
                    'is_active' => true,
                ]);
                $page->save();
            }

            $pages[$definition['slug']] = $page;
        }

        return $pages;
    }

    /**
     * @param  array<string, Page>  $pages
     */
    private function seedHeaderNavigation(array $pages): void
    {
        $order = 0;
        foreach ($this->headerNavigationOrder() as $slug => $label) {
            $page = $pages[$slug] ?? null;
            if (! $page instanceof Page) {
                continue;
            }

            SiteNavigationItem::query()->updateOrCreate(
                [
                    'zone' => SiteNavigationItem::ZONE_HEADER,
                    'page_id' => $page->id,
                ],
                [
                    'sort_order' => $order,
                    'custom_label' => $label,
                ]
            );

            $order++;
        }
    }

    /**
     * @param  list<string>  $blockSlugs
     */
    private function buildContent(array $blockSlugs): string
    {
        return Page::buildContentFromParts(
            array_map(static fn (string $slug): array => ['type' => 'block', 'slug' => $slug], $blockSlugs)
        );
    }

    /**
     * @return list<array{slug: string, title: string, meta_title: string, meta_description: string, h1: string, blocks: list<string>}>
     */
    private function pageDefinitions(): array
    {
        return [
            [
                'slug' => 'home',
                'title' => 'Home',
                'meta_title' => 'MEDCA Consultancy — Business Consultancy in India',
                'meta_description' => 'Trusted, expert-led business consultancy across India: consulting, consulting, lab collection, and 24×7 business support within 25 km of India.',
                'h1' => 'Business Consultancy, delivered to your doorstep in India.',
                'blocks' => [
                    'hero-home',
                    'services-overview-home',
                    'near-you-home',
                    'locations-overview-home',
                    'cta-home',
                ],
            ],
            [
                'slug' => 'about-us',
                'title' => 'About Us',
                'meta_title' => 'About MEDCA Consultancy — Our Mission, Doctors, and Care Philosophy',
                'meta_description' => 'MEDCA Consultancy is a India-based business consultancy provider, built around a expert-led care model and a 25 km service belt around India.',
                'h1' => 'About MEDCA Consultancy.',
                'blocks' => [
                    'hero-about',
                    'body-about',
                ],
            ],
            [
                'slug' => 'services',
                'title' => 'Services',
                'meta_title' => 'MEDCA Consultancy Services — Consulting, Consulting, Diagnostics & 24×7 Medical Support',
                'meta_description' => 'Explore MEDCA Consultancy services: in-core services, consulting, lab sample collection, post-surgical recovery, geriatric care, and 24×7 business support.',
                'h1' => 'Services that bring the hospital home.',
                'blocks' => [
                    'hero-services',
                    'services-grid-full',
                    'cta-services',
                ],
            ],
            [
                'slug' => 'locations',
                'title' => 'Locations',
                'meta_title' => 'Service Areas — Medca Consultancy Business Consultancy Across India',
                'meta_description' => 'We serve a focused service network — including Bannerghatta Road, BTM, Jayanagar, JP Nagar, Electronic City, HSR, Koramangala and more.',
                'h1' => 'Where Medca Consultancy cares — across India.',
                'blocks' => [
                    'hero-locations',
                    'near-you-locations',
                    'locations-coverage',
                ],
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'meta_title' => 'Contact MEDCA Consultancy — India Business Consultancy',
                'meta_description' => 'Reach MEDCA Consultancy for core services, consulting, diagnostics, and business support across India. Call, WhatsApp, or request a callback.',
                'h1' => 'Talk to a Medca Consultancy care advisor.',
                'blocks' => [
                    'hero-contact',
                    'contact-info',
                    'form-callback',
                ],
            ],
        ];
    }

    /**
     * Map of page slug → header label override (null means use the page title).
     *
     * @return array<string, string|null>
     */
    private function headerNavigationOrder(): array
    {
        return [
            'home' => 'Home',
            'about-us' => 'About Us',
            'services' => 'Services',
            'locations' => 'Locations',
            'careers' => 'Careers',
            'contact' => 'Contact Us',
        ];
    }
}
