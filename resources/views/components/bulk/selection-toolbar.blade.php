@props([
    'count' => 0,
    'actions' => ['delete', 'publish', 'unpublish', 'export'],
])

@if ($count > 0)
    <div {{ $attributes->class(['mom-card mb-4 flex flex-wrap items-center gap-3 border border-[rgba(197,160,89,0.28)] bg-[rgba(197,160,89,0.06)] px-4 py-3']) }}>
        <p class="text-sm font-semibold text-[var(--text-primary)]">
            {{ trans_choice(':count row selected|:count rows selected', $count, ['count' => $count]) }}
        </p>

        <div class="flex flex-wrap gap-2">
            @if (in_array('publish', $actions, true))
                <button type="button" wire:click="openBulkAction('publish')" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Publish Selected') }}</button>
            @endif
            @if (in_array('unpublish', $actions, true))
                <button type="button" wire:click="openBulkAction('unpublish')" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Unpublish Selected') }}</button>
            @endif
            @if (in_array('sync', $actions, true))
                <button type="button" wire:click="openBulkAction('sync')" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Sync Selected') }}</button>
            @endif
            @if (in_array('export', $actions, true))
                <button type="button" wire:click="openBulkAction('export')" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Export Selected') }}</button>
            @endif
            @if (in_array('delete', $actions, true))
                <button type="button" wire:click="openBulkAction('delete')" class="mom-cta-compact mom-cta-ghost text-xs text-[var(--danger)]">{{ __('Delete Selected') }}</button>
            @endif
            <button type="button" wire:click="deselectAllRows" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Deselect all') }}</button>
        </div>
    </div>
@endif
