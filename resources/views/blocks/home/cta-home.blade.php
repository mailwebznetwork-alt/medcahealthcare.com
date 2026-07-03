@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $ctaImage = \App\Support\BlockMediaUrl::first(is_array($blockMedia ?? null) ? $blockMedia : [], 'image', 'desktop_image');
    $headline = BlockContent::get($settings, 'cta-home', 'headline');
    $subheadline = BlockContent::get($settings, 'cta-home', 'subheadline');
    $primaryCta = BlockContent::get($settings, 'cta-home', 'primary_cta_label', 'Call Us');
    $secondaryCta = BlockContent::get($settings, 'cta-home', 'secondary_cta_label', 'WhatsApp Us');
    $secondaryUrl = trim(BlockContent::get($settings, 'cta-home', 'secondary_cta_url', ''));
    if ($secondaryUrl === '' || $secondaryUrl === '/contact') {
        $secondaryUrl = BlockContent::whatsAppUrl();
    }
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
        <a href="{{ $tel }}" class="medca-cta-solid">{{ $primaryCta !== '' ? $primaryCta : BlockContent::callUsLabel() }}</a>
        @if ($secondaryCta !== '')
            <a href="{{ $secondaryUrl }}" target="_blank" rel="noopener noreferrer" class="medca-whatsapp-btn shadow-sm" data-whatsapp-track="1" data-whatsapp-button="{{ $secondaryCta }}">{{ $secondaryCta }}</a>
        @endif
    </div>
    </div>
</x-public.section>
