@php
    use App\Models\Service;
    use App\Support\BlockContent;

    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'services-overview-home';
    $services = Service::query()
        ->publicListing()
        ->where('show_on_homepage', true)
        ->with(['seo', 'categories'])
        ->orderBy('sort_order')
        ->orderBy('title')
        ->limit(4)
        ->get();
@endphp
<x-public.section>
    <div id="services" class="scroll-mt-32">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <p class="medca-eyebrow">{{ BlockContent::get($settings, $slug, 'eyebrow') }}</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900 md:text-3xl">{{ BlockContent::get($settings, $slug, 'headline') }}</h2>
                @if (filled(BlockContent::get($settings, $slug, 'subheadline')))
                    <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 md:text-base">{{ BlockContent::get($settings, $slug, 'subheadline') }}</p>
                @endif
            </div>
            <a href="{{ BlockContent::get($settings, $slug, 'link_url') }}" class="medca-link-primary hidden md:inline-flex">{{ BlockContent::get($settings, $slug, 'link_label') }}</a>
        </div>

        @if ($services->isEmpty())
            <p class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm">{{ __('No services are published yet.') }}</p>
        @else
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($services as $service)
                    <x-public.service-card :service="$service" />
                @endforeach
            </div>
        @endif
    </div>
</x-public.section>
