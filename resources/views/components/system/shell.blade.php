@props([
    'activeSection' => 'overview',
    'welcomeLine' => null,
])

<x-admin.workspace
    :page-title="__('System')"
    :welcome-line="$welcomeLine ?? __('Integrations, webhooks, queues, and platform health.')"
    content-class="mt-10 w-full max-w-full"
>
    @if (session('status'))
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ session('status') }}</p>
    @endif

    {{ $slot }}
</x-admin.workspace>
