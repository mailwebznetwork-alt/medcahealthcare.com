{{-- Medca minimal footer (reference: centered Call · © · Powered by line). --}}
@php
    /** @var array<int, array{label: string, href: string}>|null $publicNavFooter */
    $footerNav = $publicNavFooter ?? [];
@endphp
<footer class="mt-auto border-t border-slate-200 bg-white px-4 py-7 text-center sm:px-6 md:px-6 md:py-4">
    @if (count($footerNav) > 0)
        <nav class="mb-5 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-[11px] font-medium uppercase tracking-[0.08em] text-slate-600 sm:gap-x-8 md:mb-2.5 md:gap-x-3 md:gap-y-1 md:text-[10px]" aria-label="{{ __('Footer links') }}">
            @foreach ($footerNav as $link)
                <a
                    href="{{ $link['href'] }}"
                    @if (\App\Support\PublicNav::isCurrent($link['href'])) aria-current="page" @endif
                    class="rounded-lg px-2 py-1 transition-colors duration-200 hover:text-[#0046ad] hover:underline md:px-1.5 md:py-0.5"
                >{{ $link['label'] }}</a>
            @endforeach
        </nav>
    @endif
    <p class="mx-auto max-w-4xl text-xs font-normal leading-relaxed tracking-[0.02em] md:text-[0.6875rem] md:leading-tight">
        <a
            href="tel:{{ preg_replace('/\s+/', '', config('medca.phone_tel')) }}"
            class="text-xs font-semibold text-[#0046ad] underline-offset-2 transition-colors duration-200 hover:text-[#001e5c] hover:underline md:text-[0.6875rem]"
            onclick="if(typeof gtag==='function'){gtag('event','call_click');}"
        >
            {{ __('Call') }} {{ config('medca.phone_display') }}
        </a>
        <span class="text-slate-400"> · </span>
        <span class="text-center font-medium leading-snug text-slate-800">{{ __('© Medca Healthcare Pvt Ltd. Powered by MarkOnMinds.') }}</span>
    </p>
</footer>
