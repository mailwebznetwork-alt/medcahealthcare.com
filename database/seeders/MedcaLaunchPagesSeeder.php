<?php

namespace Database\Seeders;

use App\Enums\PageCategory;
use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use App\Models\SiteNavigationItem;
use App\Services\Blocks\BlockTemplateSyncService;
use App\Services\Pages\MarketingPageBlockPatcher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ensures marketing pages, careers, navigation, and service carousel tokens are launch-ready.
 */
class MedcaLaunchPagesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            app(BlockTemplateSyncService::class)->sync(
                categories: ['home', 'about', 'services', 'locations', 'contact', 'careers', 'shared'],
            );

            $pages = $this->seedMarketingPages();
            app(MarketingPageBlockPatcher::class)->ensureRequiredNearYouBlocks();
            $this->seedHeaderNavigation($pages);
            $this->seedServicesCarouselBlock();
            $this->seedMarketingBlockCopy();

            $this->call(MedcaCareersPageSeeder::class);
        });
    }

    /**
     * @return array<string, Page>
     */
    private function seedMarketingPages(): array
    {
        $pages = [];

        foreach ($this->pageDefinitions() as $definition) {
            $pages[$definition['slug']] = Page::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'title' => $definition['title'],
                    'content' => Page::buildContentFromParts(
                        array_map(
                            static fn (string $slug): array => ['type' => 'block', 'slug' => $slug],
                            $definition['blocks']
                        )
                    ),
                    'meta_title' => $definition['meta_title'],
                    'meta_description' => $definition['meta_description'],
                    'h1' => $definition['h1'],
                    'is_active' => true,
                    'layout_mode' => PageLayoutMode::Canvas,
                    'page_category' => PageCategory::Web,
                ]
            );
        }

        return $pages;
    }

    /**
     * @param  array<string, Page>  $pages
     */
    private function seedHeaderNavigation(array $pages): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('site_navigation_items')) {
            return;
        }

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

    private function seedServicesCarouselBlock(): void
    {
        $tokens = implode("\n", array_map(
            static fn (string $code): string => '{{service:'.$code.'}}',
            [
                'homeconsulting-services',
                'elder-care',
                'support team',
                'doctor-home-visit',
                'consulting-at-home',
                'icu-care-at-home',
            ]
        ));

        Block::query()->updateOrCreate(
            ['block_slug' => 'services-block-carousel'],
            [
                'block_name' => 'Services — carousel (inserted services)',
                'code' => <<<BLADE
{$tokens}
@include('public.services.partials.services-carousel', [
    'services' => \$services,
    'sectionTitle' => __('Our professional services'),
])
BLADE,
                'is_active' => true,
                'is_managed' => true,
            ]
        );

        Page::query()->where('slug', 'services')->update([
            'content' => "{{block:hero-services}}\n{{block:services-block-carousel}}\n{{block:cta-services}}",
            'layout_mode' => PageLayoutMode::Canvas,
            'is_active' => true,
        ]);
    }

    private function seedMarketingBlockCopy(): void
    {
        $homeOverview = [
            'eyebrow' => 'Professional services',
            'headline' => 'Professional services, for your business in India',
            'link_label' => 'View all services',
            'link_url' => '/services',
            'card_consulting_title' => 'Core Services',
            'card_consulting_body' => 'Skilled consulting, wound care, and post-operative recovery with doctor oversight.',
            'card_physio_title' => 'Consulting',
            'card_physio_body' => 'Stroke, orthopaedic, and pain rehabilitation with licensed therapists for your business.',
            'card_diagnostics_title' => 'Doctor Home Visit',
            'card_diagnostics_body' => 'Physician consultations and care-plan updates without clinic wait times.',
            'card_support_title' => 'Elder & Support Support',
            'card_support_body' => 'Elder care and trained support team with consulting escalation when needed.',
        ];

        $this->mergeBlockContent('services-overview-home', $homeOverview);

        $servicesGrid = [
            'card_consulting_title' => 'Core Services',
            'card_consulting_body' => 'Injections, wound care, vitals, and recovery consulting across our India service belt.',
            'card_physio_title' => 'Consulting',
            'card_physio_body' => 'Home rehab for stroke, joints, and chronic pain — progressive, physician-aligned plans.',
            'card_diagnostics_title' => 'Doctor Home Visit',
            'card_diagnostics_body' => 'GP and specialist visits with prescriptions and consulting coordination.',
            'card_doctor_title' => 'Support Services',
            'card_doctor_body' => 'Verified attendants for daily living support with supervised clinical backup.',
            'card_geriatric_title' => 'Advisory',
            'card_geriatric_body' => 'Geriatric routines, medication support, and family-coordinated advisory for your business.',
            'card_support_title' => 'Specialized Support',
            'card_support_body' => 'Critical-care consulting and monitoring for your business after clinical feasibility review.',
        ];

        $this->mergeBlockContent('services-grid-full', $servicesGrid);
    }

    /**
     * @param  array<string, string>  $content
     */
    private function mergeBlockContent(string $slug, array $content): void
    {
        $block = Block::query()->where('block_slug', $slug)->first();
        if ($block === null) {
            return;
        }

        $settings = is_array($block->settings_json) ? $block->settings_json : [];
        $existing = is_array($settings['content'] ?? null) ? $settings['content'] : [];
        $settings['content'] = array_merge($existing, $content);
        $block->forceFill(['settings_json' => $settings])->save();
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
                'meta_title' => 'MarkOnMinds — Digital Growth Platform in India',
                'meta_description' => 'Expert-led digital growth platform in India: consulting, advisory, support team, consulting, consultations, and specialized support within 25 km of India.',
                'h1' => 'Digital Growth Platform, delivered to your doorstep in India.',
                'blocks' => ['hero-home', 'services-overview-home', 'near-you-home', 'locations-overview-home', 'cta-home'],
            ],
            [
                'slug' => 'about-us',
                'title' => 'About Us',
                'meta_title' => 'About MarkOnMinds — Expert-Led Digital Growth Platform in India',
                'meta_description' => 'MarkOnMinds is a India-based digital growth platform provider built around physician oversight, clinical protocols, and a 25 km service belt from India.',
                'h1' => 'About MarkOnMinds.',
                'blocks' => ['hero-about', 'body-about'],
            ],
            [
                'slug' => 'services',
                'title' => 'Services',
                'meta_title' => 'MarkOnMinds Services — Core Services, Advisory, Consulting & More',
                'meta_description' => 'Explore MarkOnMinds professional services: core services, advisory, support team, doctor consultations, consulting, and specialized ICU care for your business in India.',
                'h1' => 'Services that bring the hospital home.',
                'blocks' => ['hero-services', 'services-block-carousel', 'cta-services'],
            ],
            [
                'slug' => 'locations',
                'title' => 'Locations',
                'meta_title' => 'Service Areas — MarkOnMinds Digital Growth Platform Across India',
                'meta_description' => 'MarkOnMinds serves a focused service network — Bannerghatta Road, BTM, Jayanagar, JP Nagar, HSR, Koramangala, Electronic City, and more.',
                'h1' => 'Where MarkOnMinds cares — across India.',
                'blocks' => ['hero-locations', 'near-you-locations', 'locations-coverage'],
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'meta_title' => 'Contact MarkOnMinds — Book Digital Growth Platform in India',
                'meta_description' => 'Call, WhatsApp, or request a callback from MarkOnMinds for core services, advisory, consulting, and business support across India.',
                'h1' => 'Talk to a MarkOnMinds care advisor.',
                'blocks' => ['hero-contact', 'contact-info', 'form-callback'],
            ],
        ];
    }

    /**
     * @return array<string, string>
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
