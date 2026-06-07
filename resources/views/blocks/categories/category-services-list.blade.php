@php
    $services = $categoryServices ?? $services ?? collect();
    $featured = $featuredServices ?? collect();
    $topRated = $topRatedServices ?? collect();
@endphp
<x-public.section>
    @if ($featured->isNotEmpty())
        <div class="mb-10">
            <h2 class="text-xl font-semibold text-slate-900">{{ __('Featured services') }}</h2>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($featured as $service)
                    <li><a href="{{ $service->publicUrl() }}" class="font-medium text-[var(--medca-navy)] hover:underline">{{ $service->title }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($topRated->isNotEmpty())
        <div class="mb-10">
            <h2 class="text-xl font-semibold text-slate-900">{{ __('Top rated') }}</h2>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($topRated as $service)
                    <li><a href="{{ $service->publicUrl() }}" class="font-medium text-[var(--medca-navy)] hover:underline">{{ $service->title }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($services->isNotEmpty())
        <h2 class="text-xl font-semibold text-slate-900">{{ __('Services in this category') }}</h2>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                <li class="rounded-lg border border-slate-200 p-4">
                    <a href="{{ $service->publicUrl() }}" class="font-semibold text-[var(--medca-navy)] hover:underline">{{ $service->title }}</a>
                    @if (filled($service->short_summary))
                        <p class="mt-2 text-sm text-slate-600">{{ $service->short_summary }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-public.section>
