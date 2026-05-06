{{-- Medca minimal footer (reference: centered Call · © · Powered by line). --}}
@php
    /** @var array<int, array{label: string, href: string}>|null $publicNavFooter */
    $footerNav = $publicNavFooter ?? [];
@endphp
<footer class="border-t border-[#eeeeee] bg-white px-4 py-8 text-center sm:px-6">
    @if (count($footerNav) > 0)
        <nav class="mb-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs font-semibold uppercase tracking-widest text-slate-600" aria-label="{{ __('Footer links') }}">
            @foreach ($footerNav as $link)
                <a href="{{ $link['href'] }}" class="rounded-lg px-2 py-1 transition hover:text-[#002366] hover:underline">{{ $link['label'] }}</a>
            @endforeach
        </nav>
    @endif
    <p class="text-xs leading-relaxed text-slate-600">
        <a
            href="tel:{{ preg_replace('/\s+/', '', config('medca.phone_tel')) }}"
            class="font-semibold text-[#002366] underline-offset-2 hover:text-[#6f42c1] hover:underline"
            onclick="if(typeof gtag==='function'){gtag('event','call_click');}"
        >
            {{ __('Call') }} {{ config('medca.phone_display') }}
        </a>
        <span class="text-slate-400"> · </span>
        <span>{{ __('© Medca Healthcare Pvt Ltd. Powered by MarkOnMinds.') }}</span>
    </p>
</footer>
