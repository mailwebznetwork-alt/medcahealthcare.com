@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'body-about';
    $bullets = BlockContent::globalLinesOrBlock($settings, $slug, 'trust_bullets', 'trust_pillars');
    $promiseHeadline = BlockContent::globalOrBlock($settings, $slug, 'promise_headline', 'quality_promise_headline', 'The Medca Quality Promise');
    $promiseIntro = BlockContent::globalOrBlock($settings, $slug, 'promise_intro', 'quality_promise_intro', 'We hold ourselves to a standard higher than the industry average. Our commitment to you:');
@endphp
<x-public.section>
    <div class="space-y-10">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900 md:text-3xl">{{ BlockContent::globalOrBlock($settings, $slug, 'trust_title', 'trust_title', 'Why Choose Medca?') }}</h2>
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @foreach ($bullets as $line)
                    @php
                        $parts = array_map('trim', explode('|', $line, 2));
                        $title = $parts[0] ?? $line;
                        $body = $parts[1] ?? '';
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
                        @if ($body !== '')
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $body }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>

        <div>
            <h2 class="text-2xl font-semibold text-slate-900 md:text-3xl">{{ $promiseHeadline }}</h2>
            @if ($promiseIntro !== '')
                <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 md:text-base">{{ $promiseIntro }}</p>
            @endif
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::globalOrBlock($settings, $slug, 'mission_title', 'mission_title', 'Clinical Supervision') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ BlockContent::globalOrBlock($settings, $slug, 'mission_body', 'mission_statement') }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::globalOrBlock($settings, $slug, 'vision_title', 'vision_title', 'Transparent Communication') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ BlockContent::globalOrBlock($settings, $slug, 'vision_body', 'vision_statement') }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">{{ BlockContent::globalOrBlock($settings, $slug, 'model_title', 'care_model_title', 'Ethical Care Standards') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ BlockContent::globalOrBlock($settings, $slug, 'model_body', 'care_model') }}</p>
                </article>
            </div>
        </div>
    </div>
</x-public.section>
