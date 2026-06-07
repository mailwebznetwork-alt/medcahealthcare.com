@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
    $eyebrow = BlockContent::get($settings, 'hero-home', 'eyebrow');
    $headline = BlockContent::get($settings, 'hero-home', 'headline');
    $subheadline = BlockContent::get($settings, 'hero-home', 'subheadline');
    $primaryCta = BlockContent::get($settings, 'hero-home', 'primary_cta_label');
    $secondaryCta = BlockContent::get($settings, 'hero-home', 'secondary_cta_label');
    $tel = BlockContent::telHref();
    $phone = BlockContent::phoneDisplay();
@endphp
<x-public.hero class="medca-hero-gradient text-white" style="{{ $heroMediaStyle }}">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">{{ $eyebrow }}</p>
    <h1 class="mt-4 text-3xl font-semibold leading-tight md:text-5xl">{{ $headline }}</h1>
    <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/85 md:text-lg">{{ $subheadline }}</p>
    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ $tel }}" class="medca-cta-on-hero">{{ $primaryCta }} {{ $phone }}</a>
        <x-whatsapp.link :label="__('WhatsApp Us')">{{ __('WhatsApp Us') }}</x-whatsapp.link>
    </div>
</x-public.hero>
