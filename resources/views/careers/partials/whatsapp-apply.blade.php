@php
    /** @var \App\Models\Vacancy $vacancy */
@endphp

<div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-900">{{ __('Apply via WhatsApp') }}</h2>
    <p class="mt-2 text-sm text-slate-600">{{ __('Continue in WhatsApp for recruiter routing and ATS tracking.') }}</p>
    <a
        href="{{ $vacancy->whatsapp_apply_url }}"
        target="_blank"
        rel="noopener noreferrer"
        onclick="if(typeof gtag==='function'){gtag('event','whatsapp_click');}"
        class="mt-4 flex w-full items-center justify-center rounded-lg bg-[#25D366] px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:brightness-105"
    >{{ __('Open WhatsApp') }}</a>
</div>
