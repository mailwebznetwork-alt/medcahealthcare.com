@props([
    'activeSection' => 'integrations',
    'welcomeLine',
])

<x-app-layout :page-title="__('Settings')" :welcome-line="$welcomeLine">
    @if (session('status'))
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->has('integration'))
        <p class="mom-body-text mb-6 text-[var(--danger)]" role="alert">{{ $errors->first('integration') }}</p>
    @endif

    @include('settings.partials.nav', ['activeSection' => $activeSection])

    {{ $slot }}
</x-app-layout>
