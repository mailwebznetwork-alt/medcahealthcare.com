@php
    use App\Support\BlockContent;

    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'locations-coverage';
    $pinList = ($pinCodes ?? collect())
        ->where('is_active', true)
        ->values();
    $headline = BlockContent::get($settings, $slug, 'headline');
    $footnote = BlockContent::get($settings, $slug, 'footnote');
@endphp

<x-public.section>
    @if ($pinList->isNotEmpty())
        <x-public.locations-coverage-grid
            :areas="$pinList"
            :title="$headline"
            :initial="8"
        />
    @endif

    @if (filled($footnote))
        <p class="mt-4 text-xs leading-relaxed text-slate-500 md:text-sm">{{ $footnote }}</p>
    @endif
</x-public.section>
