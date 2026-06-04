@php
    $canBypass = auth()->user()?->canBypassArchitectSaveConstraints() ?? false;
@endphp

@if ($canBypass)
    <div class="mb-4 flex flex-wrap gap-4 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-4 py-3 text-sm text-[var(--text-secondary)]">
        <label class="inline-flex cursor-pointer items-center gap-2">
            <input type="checkbox" name="_architect_incomplete_approved" value="1" class="rounded border-[var(--border-panel-soft)]" @checked(old('_architect_incomplete_approved')) />
            <span>{{ __('Save anyway (allow empty required fields)') }}</span>
        </label>
        <label class="inline-flex cursor-pointer items-center gap-2">
            <input type="checkbox" name="_architect_overwrite_approved" value="1" class="rounded border-[var(--border-panel-soft)]" @checked(old('_architect_overwrite_approved')) />
            <span>{{ __('Overwrite duplicate slug/code') }}</span>
        </label>
    </div>
@else
    <div class="mb-4 rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] px-4 py-3 text-sm text-[var(--text-secondary)]">
        <label class="inline-flex cursor-pointer items-center gap-2">
            <input type="checkbox" name="_architect_overwrite_approved" value="1" class="rounded border-[var(--border-panel-soft)]" @checked(old('_architect_overwrite_approved')) />
            <span>{{ __('Overwrite duplicate slug/code if another record uses it') }}</span>
        </label>
    </div>
@endif

@if ($errors->has('architect_save'))
    <div class="mom-card mb-4 border border-[rgba(226,184,92,0.45)] px-4 py-3 text-sm text-mom-gold" role="alert">
        {{ $errors->first('architect_save') }}
    </div>
@endif
