@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\PinCode> $pinCodes */
    $pinCodes = $pinCodes ?? collect();
    $selectedPinIds = array_map(static fn ($v) => (int) $v, old('pincodes', $selectedPinIds ?? []));
    $fieldName = $fieldName ?? 'pincodes[]';
    $title = $title ?? __('GEO — serviceable pincodes');
    $description = $description ?? __('Select existing coverage areas from your pin code directory. No manual pin strings.');
@endphp

<section class="mom-card p-6">
    <h3 class="mom-section-title mb-4">{{ $title }}</h3>
    <p class="mom-body-text mb-4 max-w-3xl">{{ $description }}</p>
    <div
        x-data="{
            q: '',
            setAll(checked) {
                this.$refs.pinList.querySelectorAll('[data-pin-checkbox]').forEach((el) => { el.checked = checked; });
            },
            setVisible(checked) {
                this.$refs.pinList.querySelectorAll('label').forEach((label) => {
                    if (label.offsetParent === null) {
                        return;
                    }
                    const input = label.querySelector('[data-pin-checkbox]');
                    if (input) {
                        input.checked = checked;
                    }
                });
            },
        }"
        class="space-y-3"
    >
        <div class="flex flex-wrap items-center gap-2">
            <input type="search" x-model="q" class="min-w-[12rem] flex-1 rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner" placeholder="{{ __('Filter by pincode, area, city…') }}" autocomplete="off" />
            <button type="button" class="mom-cta-compact mom-cta-ghost text-xs" @click="setAll(true)">{{ __('Select all') }}</button>
            <button type="button" class="mom-cta-compact mom-cta-ghost text-xs" x-show="q.trim() !== ''" x-cloak @click="setVisible(true)">{{ __('Select filtered') }}</button>
            <button type="button" class="mom-cta-compact mom-cta-ghost text-xs" @click="setAll(false)">{{ __('Clear all') }}</button>
        </div>
        <div x-ref="pinList" class="custom-scrollbar max-h-72 overflow-y-auto rounded-mom-chrome border border-[rgba(255,255,255,0.045)] bg-[rgba(0,0,0,0.12)] p-3">
            @forelse ($pinCodes as $pc)
                @php $blob = strtolower($pc->pincode.' '.$pc->area_name.' '.$pc->city.' '.(string) $pc->locality); @endphp
                <label class="flex cursor-pointer gap-3 rounded px-2 py-1.5 hover:bg-[var(--bg-hover)]" x-show='q.trim() === "" || {{ json_encode($blob) }}.toLowerCase().includes(q.toLowerCase())'>
                    <input type="checkbox" name="{{ $fieldName }}" value="{{ $pc->id }}" data-pin-checkbox class="mt-1 rounded border-[rgba(255,255,255,0.15)]" @checked(in_array((int) $pc->id, $selectedPinIds, true)) />
                    <span class="text-sm text-[var(--text-secondary)]">
                        <span class="font-mono text-[var(--text-primary)]">{{ $pc->pincode }}</span>
                        — {{ $pc->area_name }}, {{ $pc->city }}
                        @if ($pc->locality)
                            <span class="text-[var(--text-muted)]">({{ $pc->locality }})</span>
                        @endif
                    </span>
                </label>
            @empty
                <p class="mom-subtext text-sm">
                    {{ __('No pin codes in the directory yet.') }}
                    <a href="{{ route('operations.pin-codes.directory') }}" class="text-[var(--accent)] underline underline-offset-2">{{ __('Open pin code directory') }}</a>
                </p>
            @endforelse
        </div>
    </div>
</section>
