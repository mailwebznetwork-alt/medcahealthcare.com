@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'locations-coverage';
    $pinList = ($pinCodes ?? collect())->where('is_active', true)->take(30);
@endphp
<x-public.section>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
    <h2 class="text-xl font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'headline') }}</h2>
    @if ($pinList->isNotEmpty())
        <div class="mt-5 grid gap-3 text-sm leading-relaxed text-slate-700 md:grid-cols-3">
            @foreach ($pinList->chunk(ceil($pinList->count() / 3)) as $column)
                <ul class="space-y-1.5">
                    @foreach ($column as $pin)
                        <li>{{ $pin->area_name }} ({{ $pin->pincode }})</li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    @else
        <div class="mt-5 grid gap-3 text-sm leading-relaxed text-slate-700 md:grid-cols-3">
            <ul class="space-y-1.5">
                <li>Arekere</li>
                <li>Bannerghatta Road</li>
                <li>BTM Layout</li>
                <li>Jayanagar</li>
                <li>JP Nagar</li>
            </ul>
            <ul class="space-y-1.5">
                <li>Koramangala</li>
                <li>HSR Layout</li>
                <li>Electronic City</li>
                <li>Bommanahalli</li>
                <li>Begur</li>
            </ul>
            <ul class="space-y-1.5">
                <li>Hulimavu</li>
                <li>Gottigere</li>
                <li>Hongasandra</li>
                <li>Kudlu Gate</li>
                <li>Singasandra</li>
            </ul>
        </div>
    @endif
    <p class="mt-5 text-xs leading-relaxed text-slate-500">{{ BlockContent::get($settings, $slug, 'footnote') }}</p>
    </div>
</x-public.section>
