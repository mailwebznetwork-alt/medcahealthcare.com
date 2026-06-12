@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
    $eyebrow = BlockContent::globalOrBlock($settings, 'hero-contact', 'eyebrow', 'contact_hero_eyebrow', 'Contact');
    $headline = BlockContent::globalOrBlock($settings, 'hero-contact', 'headline', 'contact_hero_headline', 'Talk to a Medca care advisor.');
    $subheadline = BlockContent::globalOrBlock($settings, 'hero-contact', 'subheadline', 'contact_hero_subheadline');
@endphp
<x-public.hero class="medca-hero-gradient text-white" style="{{ $heroMediaStyle }}">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">{{ $eyebrow }}</p>
    <h1 class="mt-4 text-3xl font-semibold leading-tight text-white md:text-5xl">{{ $headline }}</h1>
    <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/85 md:text-lg">{{ $subheadline }}</p>
</x-public.hero>
