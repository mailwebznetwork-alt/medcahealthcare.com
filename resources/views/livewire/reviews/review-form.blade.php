<div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-lg font-semibold text-slate-900">{{ __('Share your experience') }}</h3>

    @if (session('review_status'))
        <p class="mt-3 text-sm text-green-700" role="status">{{ session('review_status') }}</p>
    @endif

    @guest
        <p class="mt-3 text-sm text-slate-600">{{ __('Sign in to leave a review after a completed booking.') }}</p>
    @else
        @if (! $canReview)
            <p class="mt-3 text-sm text-slate-600">{{ __('Reviews are available after a completed service booking linked to your account.') }}</p>
        @else
            <form wire:submit="submit" class="mt-4 space-y-4">
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Rating') }}</span>
                    <select wire:model="rating" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach (range(5, 1) as $stars)
                            <option value="{{ $stars }}">{{ $stars }} {{ __('stars') }}</option>
                        @endforeach
                    </select>
                    @error('rating') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Comment') }}</span>
                    <textarea wire:model="comment" rows="4" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="{{ __('What went well?') }}"></textarea>
                    @error('comment') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <button type="submit" class="rounded-lg bg-[#0046ad] px-4 py-2 text-sm font-semibold text-white">{{ __('Submit review') }}</button>
            </form>
        @endif
    @endguest
</div>
