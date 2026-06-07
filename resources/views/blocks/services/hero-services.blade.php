@php
    use App\Support\BlockContent;

    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
    $heroServicesImage = \App\Support\BlockMediaUrl::first(is_array($blockMedia ?? null) ? $blockMedia : [], 'image', 'desktop_image', 'fallback_image');
    $eyebrow = BlockContent::get($settings, 'hero-services', 'eyebrow');
    $headline = BlockContent::get($settings, 'hero-services', 'headline');
    $subheadline = BlockContent::get($settings, 'hero-services', 'subheadline');
@endphp

<x-public.hero class="border-b border-slate-200 bg-white" style="{{ $heroMediaStyle }}">
    @if ($heroServicesImage)
        <img src="{{ $heroServicesImage }}" alt="" class="mb-6 max-h-48 w-auto rounded-xl object-cover" loading="lazy" decoding="async">
    @endif
    <p class="medca-eyebrow">{{ $eyebrow }}</p>
    <h1 class="mt-3 text-3xl font-semibold text-slate-900 md:text-4xl">{{ $headline }}</h1>
    <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">{{ $subheadline }}</p>
</x-public.hero>
