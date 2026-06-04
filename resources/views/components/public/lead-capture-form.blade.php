@props([
    'defaultService' => 'General enquiry',
    'heading' => null,
])

@php
    $heading = $heading ?? __('Request a callback');
    $errorBag = $errors ?? session('errors');
    if (! $errorBag instanceof \Illuminate\Support\MessageBag && ! $errorBag instanceof \Illuminate\Support\ViewErrorBag) {
        $errorBag = null;
    }
@endphp

<form
    method="post"
    action="{{ url('/leads') }}"
    class="mt-6 space-y-4 text-left"
>
    @csrf
    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />
    <input type="hidden" name="submission_context" value="contact_form" />
    <input type="hidden" name="landing_page" value="{{ request()->fullUrl() }}" />
    <input type="hidden" name="referrer_url" value="{{ url()->previous() }}" />

    @if (session('lead_status'))
        <p class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
            {{ session('lead_status') }}
        </p>
    @endif

    <h3 class="text-lg font-semibold text-slate-900">{{ $heading }}</h3>

    <div>
        <label for="lead-name" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Name') }}</label>
        <input id="lead-name" name="name" type="text" required value="{{ old('name') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        @if ($errorBag?->has('name')) <p class="mt-1 text-xs text-red-600">{{ $errorBag->first('name') }}</p> @endif
    </div>
    <div>
        <label for="lead-phone" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Phone') }}</label>
        <input id="lead-phone" name="phone" type="tel" required value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        @if ($errorBag?->has('phone')) <p class="mt-1 text-xs text-red-600">{{ $errorBag->first('phone') }}</p> @endif
    </div>
    <div>
        <label for="lead-service" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Service needed') }}</label>
        <input id="lead-service" name="service" type="text" required value="{{ old('service', $defaultService) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        @if ($errorBag?->has('service')) <p class="mt-1 text-xs text-red-600">{{ $errorBag->first('service') }}</p> @endif
    </div>
    <div>
        <label for="lead-message" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Message (optional)') }}</label>
        <textarea id="lead-message" name="message" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('message') }}</textarea>
    </div>
    <button type="submit" class="medca-cta-solid w-full sm:w-auto">{{ __('Submit request') }}</button>
</form>
