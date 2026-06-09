@php
    use App\Support\BlockContent;

    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
    $heroServicesImage = \App\Support\BlockMediaUrl::first(is_array($blockMedia ?? null) ? $blockMedia : [], 'image', 'desktop_image', 'fallback_image');
    $eyebrow = BlockContent::get($settings, 'hero-services', 'eyebrow');
    $headline = BlockContent::get($settings, 'hero-services', 'headline');
    $subheadline = BlockContent::get($settings, 'hero-services', 'subheadline');
@endphp

<x-public.hero class="medca-hero-gradient text-white" style="{{ $heroMediaStyle }}">
    @if ($heroServicesImage)
        <img src="{{ $heroServicesImage }}" alt="" class="mb-6 max-h-48 w-auto rounded-xl object-cover" loading="lazy" decoding="async">
    @endif
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">{{ $eyebrow }}</p>
    <h1 class="mt-4 text-3xl font-semibold leading-tight text-white md:text-5xl">{{ $headline }}</h1>
    <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/85 md:text-lg">{{ $subheadline }}</p>
</x-public.hero>
