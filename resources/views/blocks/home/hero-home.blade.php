@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
    $eyebrow = BlockContent::globalOrBlock($settings, 'hero-home', 'eyebrow', 'home_hero_eyebrow', 'Comprehensive Home Healthcare Services');
    $headline = BlockContent::globalOrBlock($settings, 'hero-home', 'headline', 'home_hero_headline');
    $subheadline = BlockContent::globalOrBlock($settings, 'hero-home', 'subheadline', 'home_hero_subheadline');
    if ($subheadline === '') {
        $subheadline = BlockContent::global('company_description_short', BlockContent::get($settings, 'hero-home', 'subheadline'));
    }
    $primaryCta = BlockContent::globalOrBlock($settings, 'hero-home', 'primary_cta_label', 'primary_cta', 'Call Us');
    $secondaryCta = BlockContent::globalOrBlock($settings, 'hero-home', 'secondary_cta_label', 'secondary_cta', 'WhatsApp Us');
    $secondaryUrl = trim(BlockContent::get($settings, 'hero-home', 'secondary_cta_url', ''));
    if ($secondaryUrl === '' || $secondaryUrl === '/contact') {
        $secondaryUrl = BlockContent::whatsAppUrl();
    }
    $tel = BlockContent::telHref();
@endphp
<x-public.hero class="medca-hero-gradient text-white" style="{{ $heroMediaStyle }}">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">{{ $eyebrow }}</p>
    <h1 class="mt-4 text-3xl font-semibold leading-tight md:text-5xl">{{ $headline }}</h1>
    <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/85 md:text-lg">{{ $subheadline }}</p>
    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ $tel }}" class="medca-cta-on-hero">{{ $primaryCta }}</a>
        <a href="{{ $secondaryUrl }}" target="_blank" rel="noopener noreferrer" class="medca-whatsapp-btn shadow-sm" data-whatsapp-track="1" data-whatsapp-button="{{ $secondaryCta }}">{{ $secondaryCta }}</a>
    </div>
</x-public.hero>
