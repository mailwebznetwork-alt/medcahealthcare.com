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
                'homenursing-services',
                'elder-care',
                'caregivers',
                'doctor-home-visit',
                'physiotherapy-at-home',
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
    'sectionTitle' => __('Our clinical services'),
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
            'eyebrow' => 'Clinical services',
            'headline' => 'Hospital-grade care, at home in Bangalore',
            'link_label' => 'View all services',
            'link_url' => '/services',
            'card_nursing_title' => 'Home Nursing',
            'card_nursing_body' => 'Skilled nursing, wound care, and post-operative recovery with doctor oversight.',
            'card_physio_title' => 'Physiotherapy',
            'card_physio_body' => 'Stroke, orthopaedic, and pain rehabilitation with licensed therapists at home.',
            'card_diagnostics_title' => 'Doctor Home Visit',
            'card_diagnostics_body' => 'Physician consultations and care-plan updates without clinic wait times.',
            'card_support_title' => 'Elder & Caregiver Support',
            'card_support_body' => 'Elder care and trained caregivers with nursing escalation when needed.',
        ];

        $this->mergeBlockContent('services-overview-home', $homeOverview);

        $servicesGrid = [
            'card_nursing_title' => 'Home Nursing',
            'card_nursing_body' => 'Injections, wound care, vitals, and recovery nursing across our Bangalore service belt.',
            'card_physio_title' => 'Physiotherapy',
            'card_physio_body' => 'Home rehab for stroke, joints, and chronic pain — progressive, physician-aligned plans.',
            'card_diagnostics_title' => 'Doctor Home Visit',
            'card_diagnostics_body' => 'GP and specialist visits with prescriptions and nursing coordination.',
            'card_doctor_title' => 'Caregiver Services',
            'card_doctor_body' => 'Verified attendants for daily living support with supervised clinical backup.',
            'card_geriatric_title' => 'Elder Care',
            'card_geriatric_body' => 'Geriatric routines, medication support, and family-coordinated elder care at home.',
            'card_support_title' => 'ICU / Specialized Care',
            'card_support_body' => 'Critical-care nursing and monitoring at home after clinical feasibility review.',
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
                'meta_title' => 'Karnataka Diagnostic Centre — Medical Laboratory in Bangalore',
                'meta_description' => 'Doctor-led Medical Laboratory services in Bangalore: lab tests, health profiles, sample collection support, and diagnostic reporting across Karnataka.',
                'h1' => 'Premium Medical Laboratory services, delivered to your doorstep in Bangalore.',
                'blocks' => ['hero-home', 'services-overview-home', 'near-you-home', 'locations-overview-home', 'cta-home'],
            ],
            [
                'slug' => 'about-us',
                'title' => 'About Us',
                'meta_title' => 'About Karnataka Diagnostic Centre — Doctor-Led Medical Laboratory Services in Bangalore',
                'meta_description' => 'Karnataka Diagnostic Centre is a Bangalore-based medical laboratory services provider built around physician oversight, clinical protocols, and a diagnostics network across Karnataka.',
                'h1' => 'About Karnataka Diagnostic Centre.',
                'blocks' => ['hero-about', 'body-about'],
            ],
            [
                'slug' => 'services',
                'title' => 'Services',
                'meta_title' => 'Medca Services — Home Nursing, Elder Care, Physiotherapy & More',
                'meta_description' => 'Explore Medca clinical services: home nursing, elder care, caregivers, doctor sample collection support, physiotherapy, and specialized ICU care at home in Bangalore.',
                'h1' => 'Services that bring the hospital home.',
                'blocks' => ['hero-services', 'services-block-carousel', 'cta-services'],
            ],
            [
                'slug' => 'locations',
                'title' => 'Locations',
                'meta_title' => 'Service Areas — Medca Medical Laboratory Services Across Bangalore',
                'meta_description' => 'Medca serves a diagnostics network across Karnataka — Bannerghatta Road, BTM, Jayanagar, JP Nagar, HSR, Koramangala, Electronic City, and more.',
                'h1' => 'Where Medca cares — across Bangalore.',
                'blocks' => ['hero-locations', 'near-you-locations', 'locations-coverage'],
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'meta_title' => 'Contact Karnataka Diagnostic Centre — Book Medical Laboratory Services in Bangalore',
                'meta_description' => 'Call, WhatsApp, or request a callback from Karnataka Diagnostic Centre for lab tests, health packages, sample collection support, and diagnostic reporting across Bangalore.',
                'h1' => 'Talk to a Medca care advisor.',
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
