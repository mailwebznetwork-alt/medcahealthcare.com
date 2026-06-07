@php
    use App\Models\Review;
    use App\Models\Service;

    $approvedReviews = Review::query()
        ->where('status', Review::STATUS_APPROVED)
        ->with('service:id,title,service_code')
        ->latest()
        ->limit(3)
        ->get();

    $avgRating = Review::query()->where('status', Review::STATUS_APPROVED)->avg('rating');
    $reviewCount = Review::query()->where('status', Review::STATUS_APPROVED)->count();

    $topServices = Service::query()
        ->publicListing()
        ->where('is_featured', true)
        ->orderBy('sort_order')
        ->limit(4)
        ->get();

    if ($topServices->isEmpty()) {
        $topServices = Service::query()->publicListing()->orderBy('sort_order')->limit(4)->get();
    }
@endphp

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
                    <p class="text-sm font-semibold text-slate-800">{{ __('Doctor-led home healthcare') }}</p>
                    <p class="text-xs text-slate-600">{{ __('25 km belt around Arekere, Bangalore') }}</p>
                </div>
            </div>
        @endif

        @if ($approvedReviews->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900 md:text-2xl">{{ __('What families say about Medca') }}</h2>
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

        @if ($topServices->isNotEmpty())
            <div class="mt-10">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-xl font-semibold text-slate-900 md:text-2xl">{{ __('Popular services') }}</h2>
                    <a href="{{ url('/services-catalog') }}" class="text-sm font-semibold text-medca-primary hover:underline">{{ __('View all services') }}</a>
                </div>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($topServices as $svc)
                        <li>
                            <a href="{{ $svc->publicUrl() }}" class="block h-full rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-medca-primary/30 hover:shadow-md">
                                <h3 class="text-sm font-semibold text-slate-900">{{ $svc->title }}</h3>
                                @if (filled($svc->short_summary))
                                    <p class="medca-card-body mt-2">{{ \Illuminate\Support\Str::limit(strip_tags((string) $svc->short_summary), 90) }}</p>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-public.content-shell>
</x-public.full-bleed>
