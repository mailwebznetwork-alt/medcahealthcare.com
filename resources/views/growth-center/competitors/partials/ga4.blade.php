<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('GA4') }}</h2>
    <p class="mom-body-text mt-2 text-[var(--text-secondary)]">{{ __('Analytics Data API — selectable 7 / 28 / 90 day windows, channels, devices, countries. Configure property ID and service account JSON in Settings → Integrations.') }}</p>
    <div class="mt-6">
        @livewire('growth.ga4-dashboard')
    </div>
</section>
