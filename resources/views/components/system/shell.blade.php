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

    @if ($errors->any())
        <div class="mom-card mb-6 border border-[var(--danger)]/40 bg-[rgba(220,38,38,0.08)] p-4" role="alert">
            <p class="mom-micro font-semibold uppercase tracking-wide text-[var(--danger)]">{{ __('Could not save') }}</p>
            <ul class="mom-body-text mt-2 list-inside list-disc text-[var(--text-secondary)]">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{ $slot }}
</x-admin.workspace>
