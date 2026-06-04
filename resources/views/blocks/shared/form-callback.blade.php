@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $headline = BlockContent::get($settings, 'form-callback', 'headline');
    $subheadline = BlockContent::get($settings, 'form-callback', 'subheadline');
@endphp
<x-blocks.element-wrap tone="muted">
    <h2 class="text-2xl font-semibold">{{ $headline }}</h2>
    <p class="mt-2 text-slate-600">{{ $subheadline }}</p>
    <x-public.lead-capture-form :heading="__('')" :default-service="__('Callback request')" />
</x-blocks.element-wrap>
