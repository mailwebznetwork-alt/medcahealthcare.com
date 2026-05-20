<?php

namespace App\Services\Operations;

use App\Enums\PageLayoutMode;
use App\Models\Block;
use App\Models\Page;
use App\Models\Service;

class ServiceDetailPageProvisioner
{
    public function __construct(
        private readonly ServiceDetailPageSeoSync $seoSync,
    ) {}

    public function suggestedSlug(Service $service): string
    {
        $pattern = (string) config('public_pages.service_detail_page_slug_pattern', 'service-{code}');

        return str_replace('{code}', $service->service_code, $pattern);
    }

    public function findPageBySuggestedSlug(Service $service): ?Page
    {
        return Page::query()
            ->where('slug', $this->suggestedSlug($service))
            ->first();
    }

    /**
     * Create (or reuse) a canvas Site Architect page for /services/{code} and link the service.
     */
    public function provision(Service $service): Page
    {
        $slug = $this->suggestedSlug($service);

        $page = Page::query()->where('slug', $slug)->first();

        if ($page === null) {
            $this->ensureStarterBlocks();

            $page = Page::query()->create([
                'title' => $service->title.' — '.__('Service detail'),
                'slug' => $slug,
                'content' => "{{block:service-detail-hero}}\n{{block:service-detail-related}}",
                'is_active' => true,
                'layout_mode' => PageLayoutMode::Canvas,
                'meta_title' => $service->title,
            ]);
        }

        if ($service->detail_page_id !== $page->id) {
            $service->forceFill(['detail_page_id' => $page->id])->save();
        }

        $service->loadMissing(['seo', 'faqs', 'schema']);
        $this->seoSync->migrateFromServiceIfEmpty($service, $page);

        return $page->fresh(['faqs']);
    }

    private function ensureStarterBlocks(): void
    {
        Block::query()->updateOrCreate(
            ['block_slug' => 'service-detail-hero'],
            [
                'block_name' => 'Service detail — hero (uses $service)',
                'code' => <<<'BLADE'
{{-- Rendered at /services/CODE. $service is always set; add {{service:your-code}} for the same row as $caregivers-style vars. --}}
<section class="w-full" data-service-detail-hero>
    <header>
        <h1>{{ $service->seo?->h1 ?: $service->title }}</h1>
        @if (filled($service->short_summary))
            <p>{{ $service->short_summary }}</p>
        @endif
        @if ($service->hasPriceRange())
            <p>{{ $service->price_range }}</p>
        @endif
    </header>
    @if (filled($service->description))
        <div>{!! $service->description !!}</div>
    @endif
</section>
BLADE,
                'is_active' => true,
            ]
        );

        Block::query()->updateOrCreate(
            ['block_slug' => 'service-detail-related'],
            [
                'block_name' => 'Service detail — related services (Insert service tokens)',
                'code' => <<<'BLADE'
{{-- Block Factory → Insert service (one line per related offering). Same services table; not a separate “sub-service” type. --}}
{{-- Example for home nursing — replace codes with yours from Operations → Services: --}}
{{-- {{service:icu-care-at-home}} --}}
{{-- {{service:palliative-care-at-home}} --}}
{{-- {{service:post-operative-care-at-home}} --}}

@include('public.services.partials.services-carousel', [
    'services' => $services,
    'sectionTitle' => __('Related services'),
])
BLADE,
                'is_active' => true,
            ]
        );
    }
}
