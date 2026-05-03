<x-operations.workspace>
    <h2 class="mom-section-title mb-8">{{ __('Add pin code') }}</h2>
    <form method="post" action="{{ route('operations.pin-codes.store') }}" class="space-y-8">
        @csrf
        @include('operations.pin-codes._form')
        <div class="flex flex-wrap gap-3">
            <x-primary-button variant="mom">{{ __('Save pin code') }}</x-primary-button>
            <a href="{{ route('operations.pin-codes.directory') }}" class="inline-flex items-center justify-center rounded-mom-md border border-[rgba(255,255,255,0.045)] bg-[rgba(255,255,255,0.03)] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-[var(--text-secondary)] shadow-mom-inner transition-all duration-320 ease-premium hover:border-[rgba(212,169,95,0.16)] hover:text-[var(--text-primary)]">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-operations.workspace>
