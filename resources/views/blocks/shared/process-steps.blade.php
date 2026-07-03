@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'process-steps';
    $stepsRaw = BlockContent::globalOrBlock($settings, $slug, 'steps', 'care_process_steps', '');
    $steps = [];
    foreach (array_values(array_filter(array_map('trim', explode("\n", $stepsRaw)))) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        $steps[] = ['title' => $parts[0] ?? '', 'body' => $parts[1] ?? ''];
    }
@endphp
<x-blocks.element-wrap tone="light">
    <x-blocks.marketing-headline :slug="$slug" :block-settings="$settings" class="text-2xl font-semibold" />
    @php $subheadline = BlockContent::get($settings, $slug, 'subheadline'); @endphp
    @if ($subheadline !== '')
        <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 md:text-base">{{ $subheadline }}</p>
    @endif
    @if ($steps !== [])
        <ol class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-5">
            @foreach ($steps as $index => $step)
                <li class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-medca-primary">Step {{ $index + 1 }}</span>
                    <h3 class="mt-2 font-semibold text-slate-900">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    @endif
</x-blocks.element-wrap>
