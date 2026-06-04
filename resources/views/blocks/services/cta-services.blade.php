@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $ctaImage = \App\Support\BlockMediaUrl::first(is_array($blockMedia ?? null) ? $blockMedia : [], 'image', 'badge');
    $headline = BlockContent::get($settings, 'cta-services', 'headline');
    $subheadline = BlockContent::get($settings, 'cta-services', 'subheadline');
    $ctaLabel = BlockContent::get($settings, 'cta-services', 'primary_cta_label');
    $ctaUrl = BlockContent::get($settings, 'cta-services', 'primary_cta_url');
@endphp
<x-public.section class="bg-slate-50">
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center shadow-sm md:p-8">
    @if ($ctaImage)
        <img src="{{ $ctaImage }}" alt="" class="mx-auto mb-6 max-h-36 rounded-xl object-cover" loading="lazy" decoding="async">
    @endif
    <h2 class="text-xl font-semibold text-slate-900 md:text-2xl">{{ $headline }}</h2>
    <p class="mt-3 text-sm leading-relaxed text-slate-600 md:text-base">{{ $subheadline }}</p>
    <a href="{{ $ctaUrl }}" class="medca-cta-solid mt-5">{{ $ctaLabel }}</a>
    </div>
</x-public.section>
