<footer class="border-t border-slate-200 bg-white px-4 py-8 text-center text-xs text-slate-500 sm:px-6">
    <div class="mx-auto max-w-7xl space-y-2">
        <p>{{ __('© Medca Healthcare Pvt Ltd. Powered by MarkOnMinds') }}</p>
        <p>
            <a
                href="tel:{{ preg_replace('/\s+/', '', config('medca.phone_tel')) }}"
                onclick="if(typeof gtag==='function'){gtag('event','call_click');}"
                class="font-semibold text-[#0046ad] underline-offset-2 hover:underline"
            >
                {{ __('Call') }} {{ config('medca.phone_display') }}
            </a>
        </p>
    </div>
</footer>
