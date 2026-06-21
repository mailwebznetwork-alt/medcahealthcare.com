@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'body-about';
    $bullets = BlockContent::globalLinesOrBlock($settings, $slug, 'trust_bullets', 'trust_pillars');
@endphp
<x-public.section>
    <div class="grid gap-6 md:grid-cols-3">
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">{{ BlockContent::globalOrBlock($settings, $slug, 'mission_title', 'mission_title', 'Our mission') }}</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ BlockContent::globalOrBlock($settings, $slug, 'mission_body', 'mission_statement') }}</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">{{ BlockContent::globalOrBlock($settings, $slug, 'vision_title', 'vision_title', 'Our vision') }}</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ BlockContent::globalOrBlock($settings, $slug, 'vision_body', 'vision_statement') }}</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">{{ BlockContent::globalOrBlock($settings, $slug, 'model_title', 'care_model_title', 'Our care model') }}</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ BlockContent::globalOrBlock($settings, $slug, 'model_body', 'care_model') }}</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-3">
        <h2 class="text-xl font-semibold text-slate-900">{{ BlockContent::globalOrBlock($settings, $slug, 'trust_title', 'trust_title', 'Why India families trust us') }}</h2>
        <ul class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600">
            @foreach ($bullets as $line)
                <li>• {{ $line }}</li>
            @endforeach
        </ul>
    </article>
    </div>
</x-public.section>
