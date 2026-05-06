@props([
    'activeSection' => 'integrations',
    'welcomeLine',
])

<x-app-layout :page-title="__('Settings')" :welcome-line="$welcomeLine">
    <div class="operations-workspace">
        <div class="mom-backend-tabstrip">
            @include('settings.partials.nav', ['activeSection' => $activeSection])
        </div>

        <div class="mt-10 w-full max-w-full">
            @if (session('status'))
                <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->has('integration'))
                <p class="mom-body-text mb-6 text-[var(--danger)]" role="alert">{{ $errors->first('integration') }}</p>
            @endif

            {{ $slot }}
        </div>
    </div>
</x-app-layout>
