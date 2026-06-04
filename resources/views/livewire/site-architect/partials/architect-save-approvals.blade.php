@php
    $hasArchitectSaveError = $errors->has('architect_save');
    $hasOverwriteError = collect($errors->getMessages())->contains(
        fn (array $messages): bool => collect($messages)->contains(
            fn (string $msg): bool => str_contains($msg, 'Already used by')
        )
    );
@endphp

@if ($hasArchitectSaveError && $this->architectSaveBypassEligible())
    <div class="mom-card mb-4 border border-[rgba(226,184,92,0.45)] bg-[rgba(226,184,92,0.1)] px-4 py-3 text-sm text-[var(--text-secondary)]" role="alert">
        <p class="font-semibold text-mom-gold">{{ __('Incomplete save') }}</p>
        <p class="mt-1">{{ $errors->first('architect_save') }}</p>
        <button
            type="button"
            wire:click="{{ $incompleteSaveAction ?? 'confirmArchitectIncompleteSave' }}"
            class="mt-3 rounded-mom-chrome border border-[rgba(197,160,89,0.35)] bg-[rgba(197,160,89,0.15)] px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-mom-gold"
        >
            {{ __('Save anyway') }}
        </button>
    </div>
@endif

@if ($hasOverwriteError)
    <div class="mom-card mb-4 border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.08)] px-4 py-3 text-sm text-[var(--text-secondary)]" role="alert">
        <p class="font-semibold text-[var(--danger)]">{{ __('Duplicate found') }}</p>
        <p class="mt-1">{{ __('Another record already uses this value. Overwrite will rename their slug/code and save yours.') }}</p>
        <button
            type="button"
            wire:click="{{ $overwriteSaveAction ?? 'confirmArchitectOverwriteSave' }}"
            class="mt-3 rounded-mom-chrome border border-[rgba(226,92,92,0.35)] bg-[rgba(226,92,92,0.12)] px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-[var(--danger)]"
        >
            {{ __('Overwrite & save') }}
        </button>
    </div>
@endif
