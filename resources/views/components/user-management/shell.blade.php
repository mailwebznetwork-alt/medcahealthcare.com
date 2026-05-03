@props([
    'pageTitle',
    'welcomeLine' => null,
])

@push('mom-module-toolbar')
    @include('user-management.partials.toolbar')
@endpush

<x-app-layout
    :page-title="$pageTitle"
    :welcome-line="$welcomeLine ?? __('Directory, access, and operational identity in one place.')"
>
    {{ $slot }}
</x-app-layout>
