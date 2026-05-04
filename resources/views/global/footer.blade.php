<footer class="border-t border-[rgba(255,255,255,0.06)] bg-[var(--bg-sidebar)] px-4 py-8 text-center text-xs text-[var(--text-muted)] sm:px-6">
    <div class="mx-auto max-w-6xl space-y-2">
        <p>{{ __('© Medca Healthcare Pvt Ltd. Powered by MarkOnMinds') }}</p>
        <p>
            <a href="tel:{{ preg_replace('/\s+/', '', config('medca.phone_tel')) }}" onclick="if(typeof gtag==='function'){gtag('event','call_click');}" class="text-[var(--accent-gold)] underline-offset-2 hover:underline">
                {{ __('Call') }} {{ config('medca.phone_display') }}
            </a>
        </p>
    </div>
</footer>
