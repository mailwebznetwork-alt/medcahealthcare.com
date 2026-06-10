@props([
    'open' => false,
    'preview' => [],
    'confirmText' => '',
])

@if ($open)
    <div
        wire:key="bulk-action-modal-overlay"
        class="fixed inset-0 z-[200] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="bulk-action-modal-title"
    >
        <div
            class="absolute inset-0 bg-black/60"
            wire:click="cancelBulkAction"
            aria-hidden="true"
        ></div>
        <div
            class="mom-card relative z-10 max-h-[90vh] w-full max-w-2xl overflow-y-auto p-6 shadow-mom-elevated"
            wire:click.stop
            wire:keydown.escape="cancelBulkAction"
        >
            <h2 id="bulk-action-modal-title" class="text-lg font-semibold text-[var(--text-primary)]">{{ __('Confirm bulk action') }}</h2>

            <p class="mom-subtext mt-2">
                {{ __('You are about to run :action on :count selected item(s).', [
                    'action' => $preview['action'] ?? __('action'),
                    'count' => $preview['selected_count'] ?? 0,
                ]) }}
            </p>

            @if (($preview['requires_delete_confirmation'] ?? false) === true)
                <p class="mt-4 rounded-mom-chrome border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--danger)]">
                    {{ __('This action is irreversible. Type DELETE to continue.') }}
                </p>
            @endif

            <dl class="mt-4 space-y-3 text-sm">
                @foreach ([
                    'affected_pages' => __('Affected pages'),
                    'affected_registry_rows' => __('Affected registry rows'),
                    'affected_urls' => __('Affected URLs'),
                    'affected_location_pages' => __('Affected location pages'),
                    'affected_service_pages' => __('Affected service pages'),
                    'cascading_deletions' => __('Cascading deletions'),
                ] as $key => $label)
                    @if (! empty($preview[$key] ?? []))
                        <div>
                            <dt class="font-semibold text-[var(--text-primary)]">{{ $label }}</dt>
                            <dd class="mom-subtext mt-1">
                                <ul class="list-inside list-disc space-y-1">
                                    @foreach ((array) $preview[$key] as $line)
                                        <li>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            </dd>
                        </div>
                    @endif
                @endforeach

                @if (($preview['affected_mappings'] ?? 0) > 0)
                    <div>
                        <dt class="font-semibold text-[var(--text-primary)]">{{ __('Affected mappings') }}</dt>
                        <dd class="mom-subtext mt-1">{{ number_format((int) $preview['affected_mappings']) }}</dd>
                    </div>
                @endif
            </dl>

            @if (($preview['requires_delete_confirmation'] ?? false) === true)
                <label class="mt-4 block">
                    <span class="mom-micro text-[var(--text-muted)]">{{ __('Type DELETE to confirm') }}</span>
                    <input
                        type="text"
                        wire:model.live="bulkDeleteConfirmText"
                        class="mom-input mt-1 w-full font-mono text-sm"
                        autocomplete="off"
                        wire:keydown.enter="confirmBulkAction"
                    />
                </label>
                @error('bulkDeleteConfirmText') <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p> @enderror
            @endif

            <div class="mt-6 flex flex-wrap items-center justify-end gap-2">
                <span wire:loading wire:target="confirmBulkAction" class="mom-subtext mr-auto text-xs">{{ __('Working…') }}</span>
                <button type="button" wire:click="cancelBulkAction" class="mom-cta-compact mom-cta-ghost" wire:loading.attr="disabled" wire:target="confirmBulkAction">{{ __('Cancel') }}</button>
                <button type="button" wire:click="confirmBulkAction" class="mom-cta-compact mom-cta-primary" wire:loading.attr="disabled" wire:target="confirmBulkAction">{{ __('Confirm') }}</button>
            </div>
        </div>
    </div>
@endif
