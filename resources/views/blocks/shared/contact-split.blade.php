@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $tel = BlockContent::telHref();
    $phone = BlockContent::phoneDisplay();
    $primaryCta = BlockContent::get($settings, 'contact-split', 'primary_cta_label');
@endphp
<x-blocks.element-wrap tone="light">
    <div class="grid gap-10 lg:grid-cols-2">
        <div>
            <h2 class="text-2xl font-semibold">{{ BlockContent::get($settings, 'contact-split', 'headline') }}</h2>
            <p class="mt-3 text-slate-600">{{ BlockContent::get($settings, 'contact-split', 'subheadline') }}</p>
            <a href="{{ $tel }}" class="medca-cta-solid mt-4 inline-flex">{{ $primaryCta }} {{ $phone }}</a>
        </div>
        <div class="rounded-xl border border-slate-200 p-6">
            <p class="text-sm text-slate-600">{{ BlockContent::get($settings, 'contact-split', 'hours_line') }}</p>
            <p class="mt-2 text-sm">{{ BlockContent::get($settings, 'contact-split', 'area_line') }}</p>
        </div>
    </div>
</x-blocks.element-wrap>
