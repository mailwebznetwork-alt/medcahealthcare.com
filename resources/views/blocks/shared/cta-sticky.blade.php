@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $tel = BlockContent::telHref();
    $headline = BlockContent::get($settings, 'cta-sticky', 'headline');
    $callLabel = BlockContent::get($settings, 'cta-sticky', 'primary_cta_label');
    $waLabel = BlockContent::get($settings, 'cta-sticky', 'whatsapp_cta_label');
@endphp
<x-blocks.element-wrap tone="muted">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-lg md:p-5">
        <p class="text-sm font-semibold text-slate-900">{{ $headline }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ $tel }}" class="medca-cta-solid text-sm">{{ $callLabel }}</a>
            <x-whatsapp.link class="px-4 py-2" :label="__('WhatsApp Us')">{{ __('WhatsApp Us') }}</x-whatsapp.link>
        </div>
    </div>
</x-blocks.element-wrap>
