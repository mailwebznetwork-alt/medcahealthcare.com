<x-site-architect.workspace :page-title="__('Legacy Sections')" :welcome-line="__('Legacy / backward compatibility — multi-block groups. Prefer block tokens on Pages.')">
    <x-admin.card class="mb-6 border-amber-500/30 bg-amber-500/5">
        <p class="text-sm font-semibold text-amber-200/90">{{ __('Legacy / Backward Compatibility') }}</p>
        <p class="mom-body-text mt-2 text-sm">{{ config('platform_composition.section_library_deprecation_note') }}</p>
        <p class="mom-body-text mt-2 text-sm">
            {{ __('Existing') }}
            <code class="text-mom-gold">@{{ section:slug }}</code>
            {{ __('tokens on pages still render. New work: use') }}
            <code class="text-mom-gold">@{{ block:slug }}</code>
            {{ __('on Pages.') }}
        </p>
    </x-admin.card>

    @livewire('site-architect.section-library')
</x-site-architect.workspace>
