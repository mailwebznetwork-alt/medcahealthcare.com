<?php

namespace Database\Seeders;

use App\Enums\PageCategory;
use App\Enums\PageLayoutMode;
use App\Models\Page;
use App\Models\SiteNavigationItem;
use App\Services\Blocks\BlockTemplateSyncService;
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
        app(BlockTemplateSyncService::class)->sync(
            categories: ['careers'],
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
                'meta_title' => 'Careers — Karnataka Diagnostic Centre | Bangalore Medical Laboratory Services Jobs',
                'meta_description' => 'Explore open clinical and operations roles at Karnataka Diagnostic Centre across Bangalore. Apply online or via WhatsApp.',
                'h1' => 'Careers at Karnataka Diagnostic Centre',
                'is_active' => true,
                'layout_mode' => PageLayoutMode::Canvas,
                'page_category' => PageCategory::Web,
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
                'meta_title' => 'Careers — Karnataka Diagnostic Centre',
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
