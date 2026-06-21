<?php

namespace Database\Seeders;

use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use App\Services\Blocks\BlockTemplateSyncService;
use Illuminate\Database\Seeder;

/**
 * Strips system-imposed service/careers layouts so blocks hold data tokens
 * and minimal markup for Site Architect styling.
 */
class RefreshPublicPageLayoutsSeeder extends Seeder
{
    public function run(): void
    {
        app(BlockTemplateSyncService::class)->sync(
            slugs: [
                'services-block-carousel',
                'services-block-grid',
                'service-detail-hero',
                'service-detail-related',
                'services-detail-layout',
            ],
        );

        Block::query()->where('block_slug', 'sdfdfsdf')->update([
            'code' => "{{service:homeconsulting-services}}\n{{service:support team}}",
        ]);

        $cta = Block::query()->where('block_slug', 'cta-services')->first();
        if ($cta !== null) {
            $cta->code = str_replace('/p/contact', '/contact', (string) $cta->code);
            $cta->save();
        }

        Page::query()->updateOrCreate(
            ['slug' => 'services-detail-template'],
            [
                'title' => 'Service detail (shared layout)',
                'content' => '{{block:services-detail-layout}}',
                'is_active' => true,
                'layout_mode' => PageLayoutMode::Canvas,
            ]
        );

        Page::query()->where('slug', 'services')->update([
            'content' => "{{block:hero-services}}\n{{block:services-block-carousel}}\n{{block:cta-services}}",
            'layout_mode' => PageLayoutMode::Canvas,
        ]);
    }
}
