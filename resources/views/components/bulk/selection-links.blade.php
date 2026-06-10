@props([
    /** @var list<int> */
    'visibleIds' => [],
    'totalCount' => null,
])

<div {{ $attributes->class(['mb-3 flex flex-wrap items-center gap-2 text-xs']) }}>
    <button
        type="button"
        wire:click="selectAllRows"
        class="font-semibold text-mom-gold hover:underline"
    >
        {{ __('Select all') }}
        @if ($totalCount !== null)
            <span class="font-normal text-[var(--text-muted)]">({{ number_format((int) $totalCount) }})</span>
        @endif
    </button>
    <span class="text-[var(--text-muted)]" aria-hidden="true">·</span>
    <button
        type="button"
        wire:click="selectAllVisibleRows({{ collect($visibleIds)->values()->toJson() }})"
        class="text-[var(--text-muted)] hover:text-mom-gold hover:underline"
    >
        {{ __('This page only') }}
    </button>
    <span class="text-[var(--text-muted)]" aria-hidden="true">·</span>
    <button
        type="button"
        wire:click="deselectAllRows"
        class="text-[var(--text-muted)] hover:underline"
    >
        {{ __('Deselect all') }}
    </button>
</div>
