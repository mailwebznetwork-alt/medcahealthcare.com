@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $headline = BlockContent::get($settings, 'cta-split', 'headline');
    $subheadline = BlockContent::get($settings, 'cta-split', 'subheadline');
    $ctaLabel = BlockContent::get($settings, 'cta-split', 'primary_cta_label');
    $ctaUrl = BlockContent::get($settings, 'cta-split', 'primary_cta_url');
@endphp
<x-blocks.element-wrap tone="light">
    <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
        <div>
            <h2 class="text-2xl font-semibold">{{ $headline }}</h2>
            <p class="mt-2 text-slate-600">{{ $subheadline }}</p>
        </div>
        <a href="{{ $ctaUrl }}" class="medca-cta-solid inline-flex shrink-0">{{ $ctaLabel }}</a>
    </div>
</x-blocks.element-wrap>
