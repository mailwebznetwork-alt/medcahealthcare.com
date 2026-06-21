<x-operations.workspace>
    <h2 class="mom-section-title mb-8">{{ __('Add country') }}</h2>
    <form method="post" action="{{ route('operations.pin-codes.store') }}" class="space-y-8">
        @csrf
        @include('operations.pin-codes._form')
        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom">{{ __('Save country') }}</x-primary-button>
            <a href="{{ route('operations.pin-codes.directory') }}" class="mom-cta-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-operations.workspace>
