@props([
    'areas' => collect(),
    'title' => __('Areas We Serve'),
    'initial' => 8,
    'excludePincodeIds' => [],
    'category' => null,
    'service' => null,
])

@php
    use App\Models\Service;
    use App\Models\ServiceCategory;
    use App\Services\Public\PinCodeCoverageUrlResolver;

    $areas = $areas instanceof \Illuminate\Support\Collection ? $areas : collect($areas);
    $excludeIds = collect($excludePincodeIds)->map(fn ($id) => (int) $id)->filter()->all();
    if ($excludeIds !== []) {
        $areas = $areas->whereNotIn('id', $excludeIds)->values();
    }
    $initial = max(4, (int) $initial);
    $categoryModel = $category instanceof ServiceCategory ? $category : null;
    $serviceModel = $service instanceof Service ? $service : null;
    $urlResolver = app(PinCodeCoverageUrlResolver::class);
    $urls = $urlResolver->urlsFor($areas, $serviceModel, $categoryModel);
    $items = $areas->map(function ($pc) use ($urls): array {
        return [
            'id' => (int) $pc->id,
            'pincode' => (string) $pc->pincode,
            'area' => (string) ($pc->area_name ?: $pc->locality ?: $pc->city ?: $pc->pincode),
            'city' => (string) ($pc->city ?? ''),
            'state' => (string) ($pc->state ?? ''),
            'serviceable' => (bool) $pc->is_serviceable,
            'url' => $urls[$pc->id] ?? route('location.pincode.select', ['pincode' => $pc->pincode]),
        ];
    })->values();
    $cities = $items->pluck('city')->filter()->unique()->sort()->values();
@endphp

@if ($items->isNotEmpty())
    <section
        {{ $attributes->merge(['class' => 'medca-locations-coverage space-y-5']) }}
        x-data="medcaLocationsCoverage(@js($items), @js($cities), {{ $initial }})"
    >
        <div>
            <h2 class="text-lg font-semibold text-slate-900 md:text-xl">{{ $title }}</h2>
        </div>

        <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 md:flex-row md:flex-wrap md:items-end">
            <div class="min-w-[12rem] flex-1">
                <label for="mc-coverage-search" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search pincode or area') }}</label>
                <input
                    id="mc-coverage-search"
                    type="search"
                    x-model="query"
                    placeholder="{{ __('e.g. 560076 or JP Nagar') }}"
                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-medca-primary focus:outline-none focus:ring-2 focus:ring-medca-primary/20"
                    autocomplete="off"
                />
            </div>
            <div class="w-full md:w-44">
                <label for="mc-coverage-city" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('City') }}</label>
                <select
                    id="mc-coverage-city"
                    x-model="cityFilter"
                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-medca-primary focus:outline-none focus:ring-2 focus:ring-medca-primary/20"
                >
                    <option value="">{{ __('All cities') }}</option>
                    <template x-for="city in cities" :key="city">
                        <option :value="city" x-text="city"></option>
                    </template>
                </select>
            </div>
            <div class="w-full md:w-44">
                <label for="mc-coverage-serviceable" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Filter') }}</label>
                <select
                    id="mc-coverage-serviceable"
                    x-model="serviceableFilter"
                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-medca-primary focus:outline-none focus:ring-2 focus:ring-medca-primary/20"
                >
                    <option value="all">{{ __('All areas') }}</option>
                    <option value="serviceable">{{ __('Serviceable only') }}</option>
                </select>
            </div>
            <div class="w-full md:w-48">
                <label for="mc-coverage-sort" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Sort') }}</label>
                <select
                    id="mc-coverage-sort"
                    x-model="sort"
                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-medca-primary focus:outline-none focus:ring-2 focus:ring-medca-primary/20"
                >
                    <option value="area_asc">{{ __('Area A–Z') }}</option>
                    <option value="area_desc">{{ __('Area Z–A') }}</option>
                    <option value="pin_asc">{{ __('Pincode low–high') }}</option>
                    <option value="pin_desc">{{ __('Pincode high–low') }}</option>
                    <option value="city_asc">{{ __('City A–Z') }}</option>
                </select>
            </div>
        </div>

        <p class="text-sm text-slate-600" x-show="filtered.length > 0" x-cloak>
            <span x-text="filtered.length"></span> {{ __('areas') }}
        </p>
        <p class="text-sm text-slate-600" x-show="filtered.length === 0" x-cloak>
            {{ __('No areas match your search. Try another pincode or area name.') }}
        </p>

        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="item in visible" :key="item.id">
                <li class="group">
                    <a
                        :href="item.url"
                        class="flex h-full min-w-0 flex-col gap-1 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-medca-primary/40 hover:shadow-md"
                    >
                        <span class="font-mono text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="item.pincode"></span>
                        <span class="text-sm font-semibold text-slate-900 group-hover:text-medca-primary" x-text="item.area"></span>
                        <span class="text-xs text-slate-500" x-show="item.city" x-text="item.city"></span>
                    </a>
                </li>
            </template>
        </ul>

        <button
            type="button"
            x-show="filtered.length > initial"
            x-cloak
            @click="expanded = !expanded"
            class="text-sm font-semibold text-medca-primary underline underline-offset-2"
            x-text="expanded ? '{{ __('Show less') }}' : '{{ __('View more areas') }} (' + Math.max(0, filtered.length - initial) + ')'"
        ></button>
    </section>
@endif

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('medcaLocationsCoverage', (items, cities, initial) => ({
                    items,
                    cities,
                    initial,
                    query: '',
                    sort: 'area_asc',
                    cityFilter: '',
                    serviceableFilter: 'all',
                    expanded: false,
                    init() {
                        ['query', 'sort', 'cityFilter', 'serviceableFilter'].forEach((field) => {
                            this.$watch(field, () => {
                                this.expanded = false;
                            });
                        });
                    },
                    get filtered() {
                        const q = this.query.trim().toLowerCase();
                        let list = this.items.filter((item) => {
                            const matchQuery = !q
                                || item.pincode.includes(q)
                                || item.area.toLowerCase().includes(q)
                                || item.city.toLowerCase().includes(q);
                            const matchCity = !this.cityFilter || item.city === this.cityFilter;
                            const matchServiceable = this.serviceableFilter !== 'serviceable' || item.serviceable;
                            return matchQuery && matchCity && matchServiceable;
                        });
                        list = [...list];
                        list.sort((a, b) => {
                            switch (this.sort) {
                                case 'area_desc':
                                    return b.area.localeCompare(a.area);
                                case 'pin_asc':
                                    return a.pincode.localeCompare(b.pincode);
                                case 'pin_desc':
                                    return b.pincode.localeCompare(a.pincode);
                                case 'city_asc':
                                    return (a.city || '').localeCompare(b.city || '') || a.area.localeCompare(b.area);
                                default:
                                    return a.area.localeCompare(b.area);
                            }
                        });
                        return list;
                    },
                    get visible() {
                        return this.expanded ? this.filtered : this.filtered.slice(0, this.initial);
                    },
                }));
            });
        </script>
    @endpush
@endonce
