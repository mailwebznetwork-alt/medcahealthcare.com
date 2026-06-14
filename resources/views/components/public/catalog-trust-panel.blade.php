@props([
    'entity' => null,
    'trustSignals' => null,
])

@php
    $signals = is_array($trustSignals) ? $trustSignals : (is_array($entity?->trust_signals) ? $entity->trust_signals : []);
    $averageRating = null;
    $reviewsCount = null;

    if ($entity !== null && method_exists($entity, 'averageApprovedRating')) {
        $averageRating = $entity->averageApprovedRating();
        $reviewsCount = method_exists($entity, 'approvedReviewsCount') ? $entity->approvedReviewsCount() : null;
    }

    if ($averageRating === null && isset($signals['google_rating']) && is_numeric($signals['google_rating'])) {
        $averageRating = (float) $signals['google_rating'];
    }

    if (($reviewsCount === null || $reviewsCount === 0) && isset($signals['review_count']) && is_numeric($signals['review_count'])) {
        $reviewsCount = (int) $signals['review_count'];
    }

    $pillLabels = collect($signals)
        ->except(['google_rating', 'review_count', 'years_experience', 'certifications', 'accreditations', 'awards'])
        ->filter(fn ($v) => is_string($v) && trim($v) !== '')
        ->values()
        ->all();

    foreach (['years_experience', 'certifications', 'accreditations', 'awards'] as $key) {
        if (filled($signals[$key] ?? null) && is_string($signals[$key])) {
            $pillLabels[] = trim($signals[$key]);
        }
    }
@endphp

@if (($averageRating !== null && ($reviewsCount ?? 0) > 0) || $pillLabels !== [])
    <div {{ $attributes->class(['space-y-3']) }}>
        @if ($averageRating !== null && ($reviewsCount ?? 0) > 0)
            <div class="medca-svc-detail-rating">
                <span class="medca-svc-detail-rating-stars" aria-hidden="true">{{ str_repeat('★', min(5, (int) round($averageRating))) }}</span>
                <span class="medca-svc-detail-rating-text">
                    {{ __(':rating / 5 · :count reviews', ['rating' => number_format((float) $averageRating, 1), 'count' => (int) $reviewsCount]) }}
                </span>
            </div>
        @endif
        @if ($pillLabels !== [])
            <ul class="medca-svc-detail-trust flex flex-wrap gap-2" aria-label="{{ __('Trust highlights') }}">
                @foreach ($pillLabels as $signal)
                    <li class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200">{{ $signal }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
