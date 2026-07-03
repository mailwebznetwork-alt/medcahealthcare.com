@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'faq-accordion';
    $itemsRaw = BlockContent::globalOrBlock($settings, $slug, 'faq_items', 'home_faq_items', '');
    $items = [];
    foreach (array_values(array_filter(array_map('trim', explode("\n", $itemsRaw)))) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        $items[] = ['question' => $parts[0] ?? '', 'answer' => $parts[1] ?? ''];
    }
@endphp
<x-blocks.element-wrap tone="muted">
    <x-blocks.marketing-headline :slug="$slug" :block-settings="$settings" class="text-2xl font-semibold" />
    @if ($items !== [])
        <div class="mt-6 space-y-3">
            @foreach ($items as $item)
                <details class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <summary class="cursor-pointer font-medium text-slate-900">{{ $item['question'] }}</summary>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $item['answer'] }}</p>
                </details>
            @endforeach
        </div>
    @endif
</x-blocks.element-wrap>
