@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
    $eyebrow = BlockContent::get($settings, 'hero-digital growth platform', 'eyebrow');
    $headline = BlockContent::get($settings, 'hero-digital growth platform', 'headline');
    $subheadline = BlockContent::get($settings, 'hero-digital growth platform', 'subheadline');
    $primaryCta = BlockContent::get($settings, 'hero-digital growth platform', 'primary_cta_label');
    $secondaryCta = BlockContent::get($settings, 'hero-digital growth platform', 'secondary_cta_label');
    $secondaryUrl = BlockContent::get($settings, 'hero-digital growth platform', 'secondary_cta_url');
    $tel = BlockContent::telHref();
@endphp
<x-public.hero class="medca-hero-gradient text-white" style="{{ $heroMediaStyle }}">
    <div class="mx-auto max-w-6xl px-4 py-16 md:py-24">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">{{ $eyebrow }}</p>
        <h1 class="mt-4 text-3xl font-semibold md:text-5xl">{{ $headline }}</h1>
        <p class="mt-4 max-w-2xl text-white/85">{{ $subheadline }}</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ $tel }}" class="medca-cta-on-hero">{{ BlockContent::callUsLabel() }}</a>
            <a href="{{ $secondaryUrl }}" class="inline-flex rounded-xl border border-white/30 px-5 py-3 text-sm font-semibold">{{ $secondaryCta }}</a>
        </div>
    </div>
</x-public.hero>
