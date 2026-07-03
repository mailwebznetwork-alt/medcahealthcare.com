@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'services-overview-home';

    $formatBody = static function (string $raw): string {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $raw))));
        if ($lines === []) {
            return '';
        }

        return $lines[0];
    };

    $formatBullets = static function (string $raw): array {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $raw))));

        return count($lines) > 1 ? array_slice($lines, 1) : [];
    };

    $cards = [
        ['title' => BlockContent::get($settings, $slug, 'card_nursing_title'), 'body' => BlockContent::get($settings, $slug, 'card_nursing_body')],
        ['title' => BlockContent::get($settings, $slug, 'card_support_title'), 'body' => BlockContent::get($settings, $slug, 'card_support_body')],
        ['title' => BlockContent::get($settings, $slug, 'card_physio_title'), 'body' => BlockContent::get($settings, $slug, 'card_physio_body')],
        ['title' => BlockContent::get($settings, $slug, 'card_diagnostics_title'), 'body' => BlockContent::get($settings, $slug, 'card_diagnostics_body')],
    ];
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
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($cards as $card)
                @php
                    $intro = $formatBody($card['body']);
                    $bullets = $formatBullets($card['body']);
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">{{ $card['title'] }}</h3>
                    @if ($intro !== '')
                        <p class="medca-card-body mt-2 italic text-slate-500">{{ $intro }}</p>
                    @endif
                    @if ($bullets !== [])
                        <ul class="mt-3 space-y-1.5 text-sm leading-relaxed text-slate-600">
                            @foreach ($bullets as $bullet)
                                <li class="flex gap-2"><span class="text-medca-primary">•</span><span>{{ $bullet }}</span></li>
                            @endforeach
                        </ul>
                    @elseif ($intro === '')
                        <p class="medca-card-body mt-2">{{ $card['body'] }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</x-public.section>
