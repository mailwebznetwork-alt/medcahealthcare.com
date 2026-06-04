@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'reviews-grid';
    $reviews = collect();
    if (isset($service) && $service !== null) {
        $service->loadMissing('approvedReviews');
        $reviews = $service->approvedReviews->take(3);
    }
@endphp
<x-blocks.element-wrap tone="light">
    <x-blocks.marketing-headline class="text-2xl font-semibold" />
    <div class="mt-6 grid gap-4 md:grid-cols-3">
        @forelse ($reviews as $review)
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-amber-500">{{ str_repeat('★', (int) round((float) $review->rating)) }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit((string) $review->comment, 160) }}</p>
            </div>
        @empty
            <div class="rounded-xl border border-slate-200 p-4 md:col-span-3">
                <p class="text-sm text-slate-600">{{ BlockContent::get($settings, $slug, 'subheadline', __('Approved service reviews appear here when linked to a service detail page.')) }}</p>
            </div>
        @endforelse
    </div>
</x-blocks.element-wrap>
