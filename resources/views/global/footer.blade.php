{{-- Medca minimal footer (reference: centered Call · © · Powered by line). --}}
@php
    /** @var array<int, array{label: string, href: string}>|null $publicNavFooter */
    $footerNav = $publicNavFooter ?? [];
@endphp
<footer class="mt-auto border-t border-slate-200 bg-white px-4 py-10 text-center sm:px-6 md:py-12">
    @if (count($footerNav) > 0)
        <nav class="mb-8 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-xs font-medium uppercase tracking-[0.08em] text-slate-600" aria-label="{{ __('Footer links') }}">
            @foreach ($footerNav as $link)
                <a
                    href="{{ $link['href'] }}"
                    @if (\App\Support\PublicNav::isCurrent($link['href'])) aria-current="page" @endif
                    class="rounded-lg px-2 py-1.5 transition-colors duration-200 hover:text-[#0046ad] hover:underline"
                >{{ $link['label'] }}</a>
            @endforeach
        </nav>
    @endif
    <p class="mx-auto max-w-4xl text-sm font-normal leading-relaxed tracking-[0.02em]">
        <a
            href="tel:{{ preg_replace('/\s+/', '', config('medca.phone_tel')) }}"
            class="font-semibold text-[#0046ad] underline-offset-2 transition-colors duration-200 hover:text-[#001e5c] hover:underline"
            onclick="if(typeof gtag==='function'){gtag('event','call_click');}"
        >
            {{ __('Call') }} {{ config('medca.phone_display') }}
        </a>
        <span class="text-slate-400"> · </span>
        <span class="text-center font-medium leading-snug text-slate-800">{{ __('© Medca Healthcare Pvt Ltd. Powered by MarkOnMinds.') }}</span>
    </p>
</footer>
