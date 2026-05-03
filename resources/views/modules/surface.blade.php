@push('mom-module-toolbar')
    @include('partials.backend-module-toolbar', ['title' => $title])
@endpush

<x-app-layout
    :page-title="$title"
    :welcome-line="__('Operational workspace for this module.')"
>
    <div class="mom-card p-8">
        <h1 class="mom-title-page">{{ $title }}</h1>
        <p class="mom-body-text mt-3 max-w-2xl text-[var(--text-secondary)]">
            {{ __('This area is provisioned for your account. Connect data sources and automation from your administration tools when you are ready.') }}
        </p>
    </div>
</x-app-layout>
