<x-operations.workspace>
    @if (session('status'))
        <div class="mom-card mb-6 border border-[rgba(197,160,89,0.22)] bg-[rgba(197,160,89,0.06)] px-4 py-3 text-sm text-[var(--text-secondary)]" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mom-card mb-6 border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--danger)]">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap gap-3">
        <a href="{{ route('operations.services.edit', ['service' => $service, 'tab' => 'sub_services']) }}" class="mom-cta-ghost">{{ __('Back to :service', ['service' => $service->title]) }}</a>
        @if ($subService->exists)
            <a href="{{ $subService->publicUrl() }}" class="mom-cta-ghost" target="_blank" rel="noopener">{{ __('Open public page') }}</a>
        @endif
    </div>

    <h2 class="mom-section-title mb-6">{{ __('Edit sub-service') }}</h2>

    <form action="{{ route('operations.services.sub-services.update', [$service, $subService]) }}" method="post" class="space-y-6">
        @csrf
        @method('PUT')
        @include('operations.services.sub-services._form', ['mode' => 'edit', 'service' => $service, 'subService' => $subService])
        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom" type="submit">{{ __('Save changes') }}</x-primary-button>
            <a href="{{ route('operations.services.sub-services.index', $service) }}" class="mom-cta-ghost">{{ __('All sub-services') }}</a>
        </div>
    </form>

    <form action="{{ route('operations.services.sub-services.destroy', [$service, $subService]) }}" method="post" class="mt-8" onsubmit="return confirm('{{ __('Delete this sub-service?') }}')">
        @csrf
        @method('DELETE')
        <x-secondary-button variant="mom" type="submit" class="text-[var(--danger)]">{{ __('Delete sub-service') }}</x-secondary-button>
    </form>
</x-operations.workspace>
