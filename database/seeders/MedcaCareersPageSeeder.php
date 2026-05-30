<?php

namespace Database\Seeders;

use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use App\Models\SiteNavigationItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds /careers and /careers/{slug} CMS layouts as Site Architect blocks.
 * Idempotent — safe to re-run; replaces deprecated {{module:*}} tokens on the careers hub.
 */
class MedcaCareersPageSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedBlocks();
            $careersPage = $this->seedCareersHubPage();
            $this->seedJobDetailPage();
            $this->ensureHeaderNavigation($careersPage);
        });
    }

    private function seedBlocks(): void
    {
        Block::query()->updateOrCreate(
            ['block_slug' => 'hero-careers'],
            [
                'block_name' => 'Careers — Hero',
                'description' => 'Marketing hero for the public /careers hub.',
                'block_type' => 'Hero',
                'code' => "@include('careers.partials.hub-hero')",
                'is_active' => true,
            ]
        );

        Block::query()->updateOrCreate(
            ['block_slug' => 'careers-open-roles'],
            [
                'block_name' => 'Careers — Open roles listing',
                'description' => 'Searchable vacancy cards. Requires $vacancies from PublicPagePresenter on /careers.',
                'block_type' => 'Listing',
                'code' => "@include('careers.partials.open-roles-listing', ['vacancies' => \$vacancies ?? collect()])",
                'is_active' => true,
            ]
        );

        Block::query()->updateOrCreate(
            ['block_slug' => 'careers-job-detail-layout'],
            [
                'block_name' => 'Careers — Job detail layout',
                'description' => 'Full job detail with apply panel. Requires $vacancy on /careers/{slug}.',
                'block_type' => 'Layout',
                'code' => "@include('careers.partials.job-detail-layout', ['vacancy' => \$vacancy])",
                'is_active' => true,
            ]
        );

        // Legacy slug kept as a thin alias for older page content references.
        Block::query()->updateOrCreate(
            ['block_slug' => 'careers'],
            [
                'block_name' => 'Careers — Open roles (legacy alias)',
                'description' => 'Alias of careers-open-roles for backward compatibility.',
                'block_type' => 'Listing',
                'code' => "@include('careers.partials.open-roles-listing', ['vacancies' => \$vacancies ?? collect()])",
                'is_active' => true,
            ]
        );
    }

    private function seedCareersHubPage(): Page
    {
        $content = Page::buildContentFromParts([
            ['type' => 'block', 'slug' => 'hero-careers'],
            ['type' => 'block', 'slug' => 'careers-open-roles'],
        ]);

        return Page::query()->updateOrCreate(
            ['slug' => 'careers'],
            [
                'title' => 'Careers',
                'content' => $content,
                'meta_title' => 'Careers — Medca Health Care | Bangalore Home Healthcare Jobs',
                'meta_description' => 'Explore open clinical and operations roles at Medca Health Care across Bangalore. Apply online or via WhatsApp.',
                'h1' => 'Careers at Medca Health Care',
                'is_active' => true,
                'layout_mode' => PageLayoutMode::Canvas,
            ]
        );
    }

    private function seedJobDetailPage(): Page
    {
        return Page::query()->updateOrCreate(
            ['slug' => config('careers.job_detail_page_slug', 'careers-job-detail')],
            [
                'title' => 'Job detail',
                'content' => '{{block:careers-job-detail-layout}}',
                'meta_title' => 'Careers — Medca Health Care',
                'is_active' => true,
                'layout_mode' => PageLayoutMode::Canvas,
            ]
        );
    }

    private function ensureHeaderNavigation(Page $careersPage): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('site_navigation_items')) {
            return;
        }

        $exists = SiteNavigationItem::query()
            ->where('zone', SiteNavigationItem::ZONE_HEADER)
            ->where('page_id', $careersPage->id)
            ->exists();

        if ($exists) {
            return;
        }

        $maxSort = (int) SiteNavigationItem::query()
            ->where('zone', SiteNavigationItem::ZONE_HEADER)
            ->max('sort_order');

        SiteNavigationItem::query()->create([
            'zone' => SiteNavigationItem::ZONE_HEADER,
            'page_id' => $careersPage->id,
            'sort_order' => $maxSort + 1,
            'custom_label' => 'Careers',
        ]);
    }
}
