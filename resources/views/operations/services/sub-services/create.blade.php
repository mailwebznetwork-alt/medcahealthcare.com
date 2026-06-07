<x-operations.workspace>
    @if ($errors->any())
        <div class="mom-card mb-6 border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--danger)]">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6">
        <a href="{{ route('operations.services.edit', ['service' => $service, 'tab' => 'sub_services']) }}" class="mom-cta-ghost">{{ __('Back to :service', ['service' => $service->title]) }}</a>
    </div>

    <h2 class="mom-section-title mb-6">{{ __('Create sub-service') }}</h2>

    <form action="{{ route('operations.services.sub-services.store', $service) }}" method="post" class="space-y-6">
        @csrf
        @include('operations.services.sub-services._form', ['mode' => 'create', 'service' => $service, 'subService' => $subService])
        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom" type="submit">{{ __('Create sub-service') }}</x-primary-button>
            <a href="{{ route('operations.services.sub-services.index', $service) }}" class="mom-cta-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-operations.workspace>
