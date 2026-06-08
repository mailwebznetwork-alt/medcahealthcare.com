<x-operations.workspace>
    <h2 class="mom-section-title mb-8">{{ __('Directory') }}</h2>

    @if (session('status') === 'pin-code-created')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Pin code created.') }}</p>
    @endif
    @if (session('status') === 'pin-code-updated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Pin code updated.') }}</p>
    @endif
    @if (session('status') === 'pin-code-deleted')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Pin code removed.') }}</p>
    @endif
    @if (session('status') === 'pin-code-activated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Pin code activated.') }}</p>
    @endif
    @if (session('status') === 'pin-code-deactivated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Pin code deactivated.') }}</p>
    @endif

    @livewire('operations.pin-codes.directory')
</x-operations.workspace>
