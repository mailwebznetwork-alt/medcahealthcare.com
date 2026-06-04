<x-site-architect.workspace
    :page-title="__('Blueprint Builder')"
    :welcome-line="__('Admin setup: generate pages and block order from an industry blueprint. Daily copy edits still happen in Pages and Blocks Studio.')"
>
    @include('site-architect.partials.deployment-hub')
    @livewire('site-architect.blueprint-builder')
</x-site-architect.workspace>
