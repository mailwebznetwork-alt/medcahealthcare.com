<x-operations.workspace>
    <h2 class="mom-section-title mb-2">{{ __('Content health') }}</h2>
    <p class="mom-subtext mb-8 max-w-3xl text-[var(--text-secondary)]">
        {{ __('Master Spec content quality dashboard — thin content, missing AEO fields, entity graph gaps, and medical review queue.') }}
    </p>
    @livewire('operations.content-health-dashboard')
</x-operations.workspace>
