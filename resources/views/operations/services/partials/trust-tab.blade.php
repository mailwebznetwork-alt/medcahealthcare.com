@php
    $trust = is_array($service->trust_signals) ? $service->trust_signals : [];
    $googleRating = method_exists($service, 'averageApprovedRating') ? $service->averageApprovedRating() : null;
    $reviewCount = method_exists($service, 'approvedReviewsCount') ? $service->approvedReviewsCount() : 0;
@endphp

<section class="mom-card p-6">
    <h3 class="mom-section-title mb-4">{{ __('Trust signals') }}</h3>
    <p class="mom-subtext mb-6 text-sm">{{ __('Displayed in schema and local trust blocks. Google rating can sync from approved reviews when left blank.') }}</p>
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="trust_years" :value="__('Years of experience')" variant="mom" />
            <x-text-input id="trust_years" name="trust_signals[years_experience]" type="text" class="mt-2 block w-full" :value="old('trust_signals.years_experience', $trust['years_experience'] ?? '')" variant="mom" />
        </div>
        <div>
            <x-input-label for="trust_google_rating" :value="__('Google rating (0–5)')" variant="mom" />
            <x-text-input id="trust_google_rating" name="trust_signals[google_rating]" type="number" step="0.1" min="0" max="5" class="mt-2 block w-full" :value="old('trust_signals.google_rating', $trust['google_rating'] ?? $googleRating)" variant="mom" />
        </div>
        <div>
            <x-input-label for="trust_review_count" :value="__('Review count')" variant="mom" />
            <x-text-input id="trust_review_count" name="trust_signals[review_count]" type="number" min="0" class="mt-2 block w-full" :value="old('trust_signals.review_count', $trust['review_count'] ?? $reviewCount)" variant="mom" />
        </div>
        <div class="md:col-span-2">
            <x-input-label for="trust_certifications" :value="__('Certifications')" variant="mom" />
            <textarea id="trust_certifications" name="trust_signals[certifications]" rows="2" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('trust_signals.certifications', $trust['certifications'] ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <x-input-label for="trust_accreditations" :value="__('Accreditations')" variant="mom" />
            <textarea id="trust_accreditations" name="trust_signals[accreditations]" rows="2" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('trust_signals.accreditations', $trust['accreditations'] ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <x-input-label for="trust_awards" :value="__('Awards')" variant="mom" />
            <textarea id="trust_awards" name="trust_signals[awards]" rows="2" class="mt-2 block w-full rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)]">{{ old('trust_signals.awards', $trust['awards'] ?? '') }}</textarea>
        </div>
    </div>
</section>
