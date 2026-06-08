@props([
    'activeSection' => 'integrations',
    'welcomeLine',
])

<x-admin.workspace
    :page-title="__('Settings')"
    :welcome-line="$welcomeLine"
    content-class="mt-10 w-full max-w-full"
>
    @if (session('status'))
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->has('integration'))
        <p class="mom-body-text mb-6 text-[var(--danger)]" role="alert">{{ $errors->first('integration') }}</p>
    @endif

    {{ $slot }}
</x-admin.workspace>
