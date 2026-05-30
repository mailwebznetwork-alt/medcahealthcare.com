<div>
    @if ($open)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 px-4" role="dialog" aria-modal="true" aria-labelledby="pincode-modal-title">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
                <h2 id="pincode-modal-title" class="text-lg font-semibold text-slate-900">{{ __('Your service pincode') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('We use your pincode to show healthcare services available in your Bangalore neighbourhood.') }}</p>

                <form wire:submit="savePincode" class="mt-5 space-y-4">
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('6-digit pincode') }}</span>
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            wire:model="pincode"
                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            placeholder="560076"
                            required
                        />
                        @error('pincode') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    @if ($samplePincodes->isNotEmpty())
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Popular areas') }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($samplePincodes as $sample)
                                    <button
                                        type="button"
                                        wire:click="$set('pincode', '{{ $sample->pincode }}')"
                                        class="rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-700 hover:border-[#0046ad]"
                                    >{{ $sample->pincode }} · {{ $sample->area_name }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-100">{{ __('Later') }}</button>
                        <button type="submit" class="rounded-lg bg-[#0046ad] px-4 py-2 text-sm font-semibold text-white">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
