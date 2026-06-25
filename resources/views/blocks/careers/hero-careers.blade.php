@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $eyebrow = BlockContent::get($settings, 'hero-careers', 'eyebrow');
    if ($eyebrow === '') {
        $eyebrow = (string) config('careers.organization_name', 'LetsSee');
    }
    $headline = BlockContent::get($settings, 'hero-careers', 'headline');
    $subheadline = BlockContent::get($settings, 'hero-careers', 'subheadline');
@endphp
<x-public.hero class="medca-hero-gradient text-white">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">{{ $eyebrow }}</p>
    <h1 class="mt-4 text-3xl font-semibold leading-tight md:text-5xl">{{ $headline }}</h1>
    <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/85 md:text-lg">{{ $subheadline }}</p>
</x-public.hero>
