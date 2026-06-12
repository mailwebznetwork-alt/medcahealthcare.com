@php
    use App\Services\Public\PublicDisplayNameResolver;

    $displayNames = app(PublicDisplayNameResolver::class);
@endphp

<div class="space-y-8">
    @if (session('status') && is_string(session('status')))
        <p class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
            {{ session('status') }}
        </p>
    @endif

    <x-public.location-heading-with-pincode
        :eyebrow="$eyebrow"
        :headline="$headline"
        :subline="$subline"
        heading-tag="h2"
        :pincode-button="$pincodeButton"
        tone="light"
    />

    @if (! $locationRequired)
        <x-public.lead-action-bar />
    @endif

    @if ($locationRequired)
        <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ __('Set your Bangalore pincode to see healthcare categories available in your area.') }}
        </p>
    @elseif ($categories->isEmpty())
        <p class="text-sm text-slate-600">{{ $emptyCategoriesMessage }}</p>
    @else
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($categories as $category)
                <li>
                    <a href="{{ route('public.service-categories.show', $category->code) }}" class="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-medca-primary/30 hover:shadow-md">
                        <x-public.near-you-category-card-image :category="$category" />
                        <div class="flex flex-1 flex-col p-5">
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-medca-primary">{{ __('Care category') }}</span>
                            <h3 class="mt-1 text-base font-semibold text-slate-900 group-hover:text-medca-primary">{{ $displayNames->categoryHeadline($category) }}</h3>
                            <x-public.catalog-card-summary :model="$category" />
                            <span class="mt-4 text-sm font-semibold text-medca-primary">{{ __('View category') }} →</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        <x-public.lead-action-bar class="pt-2" />
    @endif

    @if ($pinCodeRecord)
        <x-public.location-about-coverage :pin="$pinCodeRecord" />
    @endif
</div>
