@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
    $eyebrow = BlockContent::get($settings, 'hero-locations', 'eyebrow');
    $headline = BlockContent::get($settings, 'hero-locations', 'headline');
    $subheadline = BlockContent::get($settings, 'hero-locations', 'subheadline');
@endphp
<x-public.hero class="border-b border-slate-200 bg-white" style="{{ $heroMediaStyle }}">
    <p class="medca-eyebrow">{{ $eyebrow }}</p>
    <h1 class="mt-3 text-3xl font-semibold text-slate-900 md:text-4xl">{{ $headline }}</h1>
    <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">{{ $subheadline }}</p>
</x-public.hero>
