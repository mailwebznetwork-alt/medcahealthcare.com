@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $tel = BlockContent::telHref();
    $phone = BlockContent::phoneDisplay();
@endphp
<x-public.section>
    <div class="grid gap-4 md:grid-cols-3">
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, 'contact-info', 'call_title') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ BlockContent::get($settings, 'contact-info', 'call_body') }}</p>
        <a href="{{ $tel }}" class="medca-link-primary mt-3 inline-flex">{{ $phone }}</a>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, 'contact-info', 'whatsapp_title') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ BlockContent::get($settings, 'contact-info', 'whatsapp_body') }}</p>
        <x-whatsapp.link class="mt-3" :label="__('WhatsApp Us')">{{ __('WhatsApp Us') }}</x-whatsapp.link>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, 'contact-info', 'hours_title') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ BlockContent::globalOrBlock($settings, 'contact-info', 'hours_body', 'business_hours') }}</p>
    </article>
    </div>
</x-public.section>
