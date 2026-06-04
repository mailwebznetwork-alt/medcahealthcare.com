@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'services-overview-home';
@endphp
<x-public.section>
    <div id="services" class="scroll-mt-32">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="medca-eyebrow">{{ BlockContent::get($settings, $slug, 'eyebrow') }}</p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900 md:text-3xl">{{ BlockContent::get($settings, $slug, 'headline') }}</h2>
        </div>
        <a href="{{ BlockContent::get($settings, $slug, 'link_url') }}" class="medca-link-primary hidden md:inline-flex">{{ BlockContent::get($settings, $slug, 'link_label') }}</a>
    </div>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'card_nursing_title') }}</h3>
            <p class="medca-card-body mt-2">{{ BlockContent::get($settings, $slug, 'card_nursing_body') }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'card_physio_title') }}</h3>
            <p class="medca-card-body mt-2">{{ BlockContent::get($settings, $slug, 'card_physio_body') }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'card_diagnostics_title') }}</h3>
            <p class="medca-card-body mt-2">{{ BlockContent::get($settings, $slug, 'card_diagnostics_body') }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'card_support_title') }}</h3>
            <p class="medca-card-body mt-2">{{ BlockContent::get($settings, $slug, 'card_support_body') }}</p>
        </article>
    </div>
    </div>
</x-public.section>
