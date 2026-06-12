<div>
    @if ($open)
        <div
            class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/60 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="pincode-modal-title"
            wire:keydown.escape="closeModal"
        >
            <div class="max-h-[90dvh] w-full max-w-md overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
                <h2 id="pincode-modal-title" class="text-lg font-semibold text-slate-900">{{ __('Your service pincode') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('We use your pincode to show healthcare services available in your Bangalore neighbourhood.') }}</p>

                <form class="mt-5 space-y-4" wire:submit.prevent="savePincode">
                    <label class="relative block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('6-digit pincode') }}</span>
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            wire:model.live="pincode"
                            wire:focus="refreshPincodeSuggestions"
                            autocomplete="postal-code"
                            autocapitalize="off"
                            spellcheck="false"
                            role="combobox"
                            aria-expanded="{{ $showPincodeSuggestions ? 'true' : 'false' }}"
                            aria-controls="pincode-suggestions-list"
                            aria-autocomplete="list"
                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            placeholder="{{ __('Type to search — e.g. 56007') }}"
                        />
                        @error('pincode') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror

                        @if ($showPincodeSuggestions)
                            <div
                                id="pincode-suggestions-list"
                                role="listbox"
                                class="absolute left-0 right-0 top-full z-10 mt-1 max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg"
                            >
                                @if ($pincodeSuggestions !== [])
                                    <ul class="py-1">
                                        @foreach ($pincodeSuggestions as $suggestion)
                                            <li role="option">
                                                <button
                                                    type="button"
                                                    wire:click="selectPincode('{{ $suggestion['pincode'] }}')"
                                                    class="flex w-full flex-col gap-0.5 px-3 py-2 text-left text-sm hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"
                                                >
                                                    <span class="font-mono font-semibold text-slate-900">{{ $suggestion['pincode'] }}</span>
                                                    <span class="text-xs text-slate-600">{{ $suggestion['area_name'] }}@if ($suggestion['city'] !== '') · {{ $suggestion['city'] }}@endif</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="px-3 py-2 text-xs text-slate-500">
                                        {{ __('No serviceable pincodes match that prefix.') }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    </label>

                    <p class="text-xs text-slate-500">{{ __('Start typing your pincode — matching Bangalore service areas appear in the list.') }}</p>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-100">
                            {{ $this->dismissLabel() }}
                        </button>
                        <button
                            type="button"
                            wire:click="savePincode"
                            wire:loading.attr="disabled"
                            wire:target="savePincode"
                            class="inline-flex min-w-[5.5rem] items-center justify-center rounded-lg bg-medca-primary px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="savePincode">{{ __('Save') }}</span>
                            <span wire:loading wire:target="savePincode">{{ __('Saving…') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
