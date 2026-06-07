<x-operations.workspace>
    @if (session('status'))
        <div class="mom-card mb-6 border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm text-[var(--text-secondary)]" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mom-card mb-6 border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--danger)]" role="alert">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @error('detail_page')
        <div class="mom-card mb-6 border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--danger)]" role="alert">
            {{ $message }}
        </div>
    @enderror

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <a href="{{ route('operations.services.preview', $service) }}" class="mom-cta-ghost" target="_blank" rel="noopener">{{ __('Preview public URL') }}</a>
        <a href="{{ $service->publicUrl() }}" class="mom-cta-ghost" target="_blank" rel="noopener">{{ __('Open /services/:code', ['code' => $service->service_code]) }}</a>
        <a href="{{ route('operations.services.duplicate', $service) }}" class="mom-cta-ghost">{{ __('Duplicate') }}</a>
    </div>

    @include('operations.services._composition-guidance', [
        'service' => $service,
        'linkedDetailPage' => $linkedDetailPage,
        'patternDetailPage' => $patternDetailPage ?? null,
    ])

    @include('operations.services._detail-page-panel', [
        'service' => $service,
        'linkedDetailPage' => $linkedDetailPage,
        'suggestedDetailPageSlug' => $suggestedDetailPageSlug,
    ])

    @if (Route::has('operations.services.gemini-suggest'))
        <form id="service-gemini-suggest-form" method="post" action="{{ route('operations.services.gemini-suggest', $service) }}" class="hidden" aria-hidden="true" tabindex="-1">
            @csrf
        </form>
    @endif

    <form action="{{ route('operations.services.update', $service) }}" method="post" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')
        @include('operations.services._form', [
            'mode' => 'edit',
            'service' => $service,
            'pinCodes' => $pinCodes,
            'detailPages' => $detailPages,
            'suggestedDetailPageSlug' => $suggestedDetailPageSlug,
            'patternDetailPage' => $patternDetailPage,
            'linkedDetailPage' => $linkedDetailPage,
            'activeTab' => $activeTab ?? 'basic',
            'serviceCatalog' => $serviceCatalog ?? collect(),
            'selectedRelatedCodes' => $selectedRelatedCodes ?? [],
            'serviceReviews' => $serviceReviews ?? collect(),
            'subServices' => $subServices ?? collect(),
            'managedModule' => $managedModule ?? null,
            'customFieldValues' => $customFieldValues ?? new stdClass(),
        ])

        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom" type="submit">{{ __('Save changes') }}</x-primary-button>
            <a href="{{ route('operations.services.index') }}" class="mom-cta-ghost">{{ __('Back to list') }}</a>
        </div>
    </form>
</x-operations.workspace>
