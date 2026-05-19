@props([
    'services',
    'serviceCatalogNonce' => 0,
    'showManageLink' => false,
])

<div
    x-data
    @focus.window="$wire.refreshServiceInsertCatalog()"
>
    <label class="block text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ __('Insert service…') }}</label>
    <div class="mt-2 flex flex-wrap items-center gap-2">
        <select wire:model.live="service_choice" wire:key="service-insert-select-{{ $serviceCatalogNonce }}" class="min-w-[14rem] flex-1 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-3 py-2 text-sm text-[var(--text-primary)]">
            <option value="">{{ __('— Choose a service —') }}</option>
            @foreach ($services as $svc)
                <option value="{{ $svc->service_code }}">
                    {{ $svc->title }} ({{ $svc->service_code }})
                    @if (! $svc->is_active) — {{ __('Inactive') }}@endif
                    @if ($svc->publish_status !== \App\Enums\PublishStatus::Published) — {{ __('Draft') }}@endif
                    @if ($svc->visibility !== \App\Enums\ServiceVisibility::Public) — {{ __('Private') }}@endif
                </option>
            @endforeach
        </select>
        <button type="button" wire:click="appendServiceToken" wire:loading.attr="disabled" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] disabled:opacity-50">{{ __('Add service line') }}</button>
        <button type="button" wire:click="refreshServiceInsertCatalog" class="rounded-mom-chrome border border-[var(--border-panel-soft)] px-3 py-2 text-xs text-[var(--text-muted)] hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]">{{ __('Refresh list') }}</button>
        @if ($showManageLink)
            <a href="{{ route('operations.services.index') }}" class="text-xs text-[var(--text-muted)] underline underline-offset-2 hover:text-[var(--text-primary)]" target="_blank" rel="noopener">{{ __('Manage services →') }}</a>
        @endif
    </div>
    <p class="mom-subtext mt-2">{{ __('Each insert appends a service token to the Code textarea in the form of double-braced service:code. Draft, private, or inactive services appear here so you can stage tokens; the public site only renders active, published, public services.') }}</p>
    @error('service_choice') <span class="mt-2 block text-xs text-[var(--danger)]">{{ $message }}</span> @enderror
</div>
