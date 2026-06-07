<div class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] p-4">
    @foreach ($ids as $id)
        <input type="hidden" name="picker_gallery_media_ids[]" value="{{ $id }}" />
    @endforeach
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm font-semibold text-[var(--text-primary)]">{{ __('Add gallery image from library') }}</p>
        <button type="button" wire:click="openPicker" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Choose') }}</button>
    </div>
    @if ($ids !== [])
        <ul class="mt-3 space-y-2">
            @foreach ($ids as $id)
                <li class="flex items-center justify-between rounded border border-[rgba(255,255,255,0.06)] px-3 py-2 text-xs">
                    <span>{{ __('Media #') }}{{ $id }}</span>
                    <button type="button" wire:click="remove({{ $id }})" class="text-[var(--danger)]">{{ __('Remove') }}</button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
