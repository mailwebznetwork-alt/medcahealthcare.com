@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $headline = BlockContent::get($settings, 'cta-simple', 'headline');
    $subheadline = BlockContent::get($settings, 'cta-simple', 'subheadline');
    $ctaLabel = BlockContent::get($settings, 'cta-simple', 'primary_cta_label');
    $ctaUrl = BlockContent::get($settings, 'cta-simple', 'primary_cta_url');
@endphp
<x-blocks.element-wrap tone="muted">
    <div class="text-center">
        <h2 class="text-2xl font-semibold md:text-3xl">{{ $headline }}</h2>
        <p class="mt-3 text-slate-600">{{ $subheadline }}</p>
        <a href="{{ $ctaUrl }}" class="medca-cta-solid mt-6 inline-flex">{{ $ctaLabel }}</a>
    </div>
</x-blocks.element-wrap>
