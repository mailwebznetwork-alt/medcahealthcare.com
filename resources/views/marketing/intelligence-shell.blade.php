<x-admin.workspace
    page-title="{{ __('Marketing Intelligence') }}"
    :welcome-line="__('Attribution, conversion tracking, WhatsApp/call analytics, and executive reporting.')"
    :breadcrumbs="[
        ['label' => __('Marketing'), 'url' => route('marketing.dashboard')],
        ['label' => __('Intelligence'), 'url' => null],
    ]"
>
    <x-slot:tabs>
        @include('marketing.partials.primary-tabs', ['activeSection' => 'intelligence'])
    </x-slot:tabs>
    @livewire('marketing.intelligence-dashboard')
</x-admin.workspace>
