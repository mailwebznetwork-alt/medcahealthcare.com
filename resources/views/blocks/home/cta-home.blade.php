@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $ctaImage = \App\Support\BlockMediaUrl::first(is_array($blockMedia ?? null) ? $blockMedia : [], 'image', 'desktop_image');
    $headline = BlockContent::get($settings, 'cta-home', 'headline');
    $subheadline = BlockContent::get($settings, 'cta-home', 'subheadline');
    $secondaryCta = BlockContent::get($settings, 'cta-home', 'secondary_cta_label');
    $secondaryUrl = BlockContent::get($settings, 'cta-home', 'secondary_cta_url');
    $tel = BlockContent::telHref();
@endphp
<x-public.section class="bg-slate-50">
    <div id="contact" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center shadow-sm md:p-10">
    @if ($ctaImage)
        <img src="{{ $ctaImage }}" alt="" class="mx-auto mb-6 max-h-40 rounded-xl object-cover" loading="lazy" decoding="async">
    @endif
    <h2 class="text-2xl font-semibold text-slate-900 md:text-3xl">{{ $headline }}</h2>
    <p class="mt-3 text-sm leading-relaxed text-slate-600 md:text-base">{{ $subheadline }}</p>
    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ $tel }}" class="medca-cta-solid">{{ BlockContent::callUsLabel() }}</a>
        <a href="{{ $secondaryUrl }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">{{ $secondaryCta }}</a>
    </div>
    </div>
</x-public.section>
