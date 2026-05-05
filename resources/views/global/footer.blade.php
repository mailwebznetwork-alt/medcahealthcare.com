{{-- Medca minimal footer (reference: centered Call · © · Powered by line). --}}
<footer class="border-t border-[#eeeeee] bg-white px-4 py-8 text-center sm:px-6">
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
    @if (Route::has('login'))
        <p class="mt-3 text-[11px] text-slate-400">
            <a href="{{ route('login') }}" class="hover:text-[#6f42c1] hover:underline">{{ __('Staff login') }}</a>
        </p>
    @endif
</footer>
