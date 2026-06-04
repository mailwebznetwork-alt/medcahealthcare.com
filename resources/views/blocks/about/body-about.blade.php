@php
    use App\Support\BlockContent;
    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = 'body-about';
    $bullets = array_filter(array_map('trim', explode("\n", BlockContent::get($settings, $slug, 'trust_bullets'))));
@endphp
<x-public.section>
    <div class="grid gap-6 md:grid-cols-3">
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'mission_title') }}</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ BlockContent::get($settings, $slug, 'mission_body') }}</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'vision_title') }}</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ BlockContent::get($settings, $slug, 'vision_body') }}</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'model_title') }}</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ BlockContent::get($settings, $slug, 'model_body') }}</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-3">
        <h2 class="text-xl font-semibold text-slate-900">{{ BlockContent::get($settings, $slug, 'trust_title') }}</h2>
        <ul class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600">
            @foreach ($bullets as $line)
                <li>• {{ $line }}</li>
            @endforeach
        </ul>
    </article>
    </div>
</x-public.section>
