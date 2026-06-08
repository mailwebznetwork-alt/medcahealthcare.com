<x-admin.workspace
    page-title="{{ __('Marketing') }}"
    :welcome-line="__('Campaigns, ads, communication, and insights — GA4 detail reports live under Growth Center → GA4.')"
    :breadcrumbs="[
        ['label' => __('Marketing'), 'url' => route('marketing.dashboard')],
        ['label' => __('Dashboard'), 'url' => null],
    ]"
>
    @livewire('marketing.dashboard')
</x-admin.workspace>
