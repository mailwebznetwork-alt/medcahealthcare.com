<x-operations.workspace>
    <h2 class="mom-section-title mb-8">{{ __('Edit pin code') }}</h2>
    <form method="post" action="{{ route('operations.pin-codes.update', $pinCode) }}" class="space-y-8">
        @csrf
        @method('PUT')
        @include('operations.pin-codes._form')
        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom">{{ __('Save changes') }}</x-primary-button>
            <a href="{{ route('operations.pin-codes.directory') }}" class="mom-cta-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-operations.workspace>
