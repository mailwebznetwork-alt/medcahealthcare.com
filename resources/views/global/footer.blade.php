{{-- MarkOnMinds footer with brand positioning and compact navigation. --}}
@php
    $footerNav = app(\App\Services\SiteNavigationResolver::class)->footerLinks();
@endphp
<footer class="mt-auto border-t border-slate-200 bg-white px-4 py-8 text-center sm:px-6 md:px-6 md:py-10">
    <div class="mx-auto mb-6 max-w-3xl">
        <p class="text-xs font-bold uppercase tracking-[0.22em] text-medca-primary">{{ __('MarkOnMinds') }}</p>
        <p class="mt-2 text-sm font-semibold text-slate-900 md:text-base">{{ __('Brand Strategy, Market Positioning & Business Growth Consultants') }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ __('We Build Businesses People Trust.') }}</p>
        <p class="mt-3 text-xs font-bold uppercase tracking-[0.18em] text-medca-primary">{{ __('Strategy First. Excellence Always. Growth With Purpose.') }}</p>
    </div>

    @if (count($footerNav) > 0)
        <nav class="mb-2 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-[11px] font-medium uppercase tracking-[0.08em] text-slate-600 sm:gap-x-6 md:mb-2.5 md:gap-x-3 md:gap-y-1 md:text-[10px]" aria-label="{{ __('Footer links') }}">
            @foreach ($footerNav as $link)
                <a
                    href="{{ $link['href'] }}"
                    @if (\App\Support\PublicNav::isCurrent($link['href'])) aria-current="page" @endif
                    class="rounded-lg px-2 py-1 transition-colors duration-200 hover:text-medca-primary hover:underline md:px-1.5 md:py-0.5"
                >{{ $link['label'] }}</a>
            @endforeach
        </nav>
    @endif
    <p class="mx-auto max-w-4xl text-xs font-normal leading-snug tracking-[0.02em] text-slate-800 md:text-[0.6875rem] md:leading-tight">
        © MarkOnMinds Pvt Ltd. Powered by MarkOnMinds
    </p>
</footer>
