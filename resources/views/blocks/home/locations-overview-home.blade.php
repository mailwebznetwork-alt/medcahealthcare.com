@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'locations-overview-home';
@endphp
<x-public.section class="bg-white">
    <div id="locations" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
    <p class="medca-eyebrow">{{ BlockContent::get($settings, $slug, 'eyebrow') }}</p>
    <h2 class="mt-2 text-2xl font-semibold text-slate-900 md:text-3xl">{{ BlockContent::get($settings, $slug, 'headline') }}</h2>
    <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 md:text-base">{{ BlockContent::get($settings, $slug, 'subheadline') }}</p>
    <a href="{{ BlockContent::get($settings, $slug, 'link_url') }}" class="medca-link-primary mt-5 inline-flex">{{ BlockContent::get($settings, $slug, 'link_label') }}</a>
    </div>
</x-public.section>
