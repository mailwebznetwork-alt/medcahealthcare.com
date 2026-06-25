@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
    $heroAboutImage = \App\Support\BlockMediaUrl::first(is_array($blockMedia ?? null) ? $blockMedia : [], 'desktop_image', 'image', 'fallback_image');
    $eyebrow = BlockContent::globalOrBlock($settings, 'hero-about', 'eyebrow', 'about_hero_eyebrow', 'About LetsSee');
    $headline = BlockContent::globalOrBlock($settings, 'hero-about', 'headline', 'about_hero_headline', 'Expert-led, family-centred digital growth platform.');
    $subheadline = BlockContent::globalOrBlock($settings, 'hero-about', 'subheadline', 'about_hero_subheadline');
    if ($subheadline === '') {
        $subheadline = BlockContent::global('company_description_long', BlockContent::get($settings, 'hero-about', 'subheadline'));
    }
@endphp
<x-public.hero class="medca-hero-gradient text-white" style="{{ $heroMediaStyle ?? '' }}">
    @if ($heroAboutImage)
        <img src="{{ $heroAboutImage }}" alt="" class="mb-6 max-h-48 w-auto rounded-xl object-cover" loading="lazy" decoding="async">
    @endif
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">{{ $eyebrow }}</p>
    <h1 class="mt-4 text-3xl font-semibold leading-tight text-white md:text-5xl">{{ $headline }}</h1>
    <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/85 md:text-lg">{{ $subheadline }}</p>
</x-public.hero>
