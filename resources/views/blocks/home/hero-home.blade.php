@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
    $eyebrow = BlockContent::globalOrBlock($settings, 'hero-home', 'eyebrow', 'home_hero_eyebrow', 'Digital Growth Platform · India');
    $headline = BlockContent::globalOrBlock($settings, 'hero-home', 'headline', 'home_hero_headline');
    $subheadline = BlockContent::globalOrBlock($settings, 'hero-home', 'subheadline', 'home_hero_subheadline');
    if ($subheadline === '') {
        $subheadline = BlockContent::global('company_description_short', BlockContent::get($settings, 'hero-home', 'subheadline'));
    }
    $content = is_array($settings['content'] ?? null) ? $settings['content'] : [];
    $bodyLines = array_values(array_filter(array_map(
        static fn (mixed $line): string => trim((string) $line),
        is_array($content['body_lines'] ?? null) ? $content['body_lines'] : []
    )));
    $mantra = trim((string) ($content['mantra'] ?? 'Strategy First. Excellence Always. Growth With Purpose.'));
    $tel = BlockContent::telHref();
@endphp
<x-public.hero class="medca-hero-gradient text-white" style="{{ $heroMediaStyle }}">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">{{ $eyebrow }}</p>
    <h1 class="mt-4 text-3xl font-semibold leading-tight md:text-5xl">{{ $headline }}</h1>
    <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/85 md:text-lg">{{ $subheadline }}</p>
    @if ($bodyLines !== [])
        <div class="mt-5 max-w-3xl space-y-2 text-sm leading-relaxed text-white/80 md:text-base">
            @foreach ($bodyLines as $line)
                <p>{{ $line }}</p>
            @endforeach
        </div>
    @endif
    @if ($mantra !== '')
        <p class="mt-6 max-w-2xl text-sm font-bold uppercase tracking-[0.18em] text-[#f5d28a] md:text-base">{{ $mantra }}</p>
    @endif
    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ url('/contact') }}" class="medca-cta-on-hero">{{ BlockContent::get($settings, 'hero-home', 'primary_cta_label') ?: __('Schedule A Strategy Consultation') }}</a>
        <x-whatsapp.link :label="__('WhatsApp Us')">{{ __('WhatsApp Us') }}</x-whatsapp.link>
    </div>
</x-public.hero>
