<div class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] p-4">
    <input type="hidden" name="{{ $fieldName }}" value="{{ $value ?? '' }}" />
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-[var(--text-primary)]">{{ $label }}</p>
            @if ($media)
                <div class="mt-2 flex items-center gap-3">
                    @if ($media->file_type === 'image')
                        <img src="{{ $media->publicUrlFor($media->thumbnail_path ?? $media->small_path ?? $media->webp_path ?? $media->file_path) }}" alt="" class="h-16 w-24 rounded object-cover" />
                    @endif
                    <div class="text-xs text-[var(--text-secondary)]">
                        <p class="font-medium">{{ $media->file_name }}</p>
                        @if ($media->width && $media->height)<p>{{ $media->width }}×{{ $media->height }}</p>@endif
                        <p>{{ __('SEO') }}: {{ $media->image_seo_score ?? '—' }}% · {{ __('Used') }}: {{ $media->usages_count ?? 0 }}</p>
                    </div>
                </div>
            @else
                <p class="mom-subtext mt-1 text-xs">{{ __('No asset selected.') }}</p>
            @endif
        </div>
        <div class="flex shrink-0 gap-2">
            <button type="button" wire:click="openPicker" class="mom-cta-compact mom-cta-ghost text-xs">{{ __('Choose from library') }}</button>
            @if ($value)
                <button type="button" wire:click="clear" class="mom-cta-compact mom-cta-ghost text-xs text-[var(--danger)]">{{ __('Clear') }}</button>
            @endif
        </div>
    </div>
</div>
