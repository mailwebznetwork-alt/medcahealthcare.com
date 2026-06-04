@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $headline = BlockContent::get($settings, 'cta-banner', 'headline');
    $ctaLabel = BlockContent::get($settings, 'cta-banner', 'primary_cta_label');
    $ctaUrl = BlockContent::get($settings, 'cta-banner', 'primary_cta_url');
@endphp
<x-blocks.element-wrap tone="brand">
    <div class="flex flex-col gap-4 rounded-2xl bg-white/10 p-6 md:flex-row md:items-center md:justify-between">
        <p class="text-lg font-semibold">{{ $headline }}</p>
        <a href="{{ $ctaUrl }}" class="medca-cta-on-hero inline-flex shrink-0">{{ $ctaLabel }}</a>
    </div>
</x-blocks.element-wrap>
