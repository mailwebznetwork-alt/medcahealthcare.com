<x-operations.workspace>
    @if ($errors->any())
        <div class="mom-card mb-6 border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--danger)]" role="alert">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('operations.services.store') }}" method="post" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @include('operations.services._form', [
            'mode' => 'create',
            'service' => $service,
            'pinCodes' => $pinCodes,
            'detailPages' => $detailPages,
            'suggestedDetailPageSlug' => $suggestedDetailPageSlug,
            'patternDetailPage' => $patternDetailPage,
            'activeTab' => $activeTab ?? 'basic',
            'serviceCatalog' => $serviceCatalog ?? collect(),
            'selectedRelatedCodes' => $selectedRelatedCodes ?? [],
            'serviceReviews' => $serviceReviews ?? collect(),
            'managedModule' => $managedModule ?? null,
            'customFieldValues' => $customFieldValues ?? new stdClass(),
        ])

        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom" type="submit">{{ __('Create service') }}</x-primary-button>
            <a href="{{ route('operations.services.index') }}" class="mom-cta-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-operations.workspace>
