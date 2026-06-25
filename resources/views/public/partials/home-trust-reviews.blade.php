@php
    use App\Models\Review;

    $approvedReviews = Review::query()
        ->where('status', Review::STATUS_APPROVED)
        ->with('service:id,title,service_code')
        ->latest()
        ->limit(3)
        ->get();

    $avgRating = Review::query()->where('status', Review::STATUS_APPROVED)->avg('rating');
    $reviewCount = Review::query()->where('status', Review::STATUS_APPROVED)->count();
    $showTrustSection = ($avgRating !== null && $reviewCount > 0) || $approvedReviews->isNotEmpty();
@endphp

@if ($showTrustSection)
<x-public.full-bleed class="border-t border-slate-200 bg-slate-50 py-10 md:py-12" data-section="home-trust">
    <x-public.content-shell>
        @if ($avgRating !== null && $reviewCount > 0)
            <div class="flex flex-wrap items-center justify-center gap-8 rounded-xl border border-slate-200 bg-white px-6 py-5 text-center shadow-sm">
                <div>
                    <p class="text-3xl font-bold text-amber-600">{{ number_format((float) $avgRating, 1) }}</p>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Average rating') }}</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-medca-primary">{{ number_format($reviewCount) }}+</p>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Patient reviews') }}</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-800">{{ __('Expert-led digital growth platform') }}</p>
                    <p class="text-xs text-slate-600">{{ __('focused service network, India') }}</p>
                </div>
            </div>
        @endif

        @if ($approvedReviews->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900 md:text-2xl">{{ __('What families say about LetsSee') }}</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach ($approvedReviews as $review)
                        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-amber-500" aria-label="{{ __('Rating') }} {{ $review->rating }}">{{ str_repeat('★', min(5, (int) $review->rating)) }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ \Illuminate\Support\Str::limit((string) $review->comment, 180) }}</p>
                            @if ($review->service)
                                <p class="mt-3 text-xs font-medium text-medca-primary">{{ $review->service->title }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </x-public.content-shell>
</x-public.full-bleed>
@endif
