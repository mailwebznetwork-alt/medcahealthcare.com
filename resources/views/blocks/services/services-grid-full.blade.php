@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'services-grid-full';
@endphp
<x-public.section>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'card_nursing_title') }}</h3>
        <p class="medca-card-body mt-2">{{ BlockContent::get($settings, $slug, 'card_nursing_body') }}</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'card_physio_title') }}</h3>
        <p class="medca-card-body mt-2">{{ BlockContent::get($settings, $slug, 'card_physio_body') }}</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'card_diagnostics_title') }}</h3>
        <p class="medca-card-body mt-2">{{ BlockContent::get($settings, $slug, 'card_diagnostics_body') }}</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'card_doctor_title') }}</h3>
        <p class="medca-card-body mt-2">{{ BlockContent::get($settings, $slug, 'card_doctor_body') }}</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'card_geriatric_title') }}</h3>
        <p class="medca-card-body mt-2">{{ BlockContent::get($settings, $slug, 'card_geriatric_body') }}</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'card_support_title') }}</h3>
        <p class="medca-card-body mt-2">{{ BlockContent::get($settings, $slug, 'card_support_body') }}</p>
    </article>
    </div>
</x-public.section>
