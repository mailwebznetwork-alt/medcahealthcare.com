@php
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
 @endphp
<x-blocks.element-wrap tone="light">
    <div class="grid items-center gap-10 lg:grid-cols-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-medca-primary">Split layout</p>
            <h1 class="mt-3 text-3xl font-semibold md:text-4xl">Care designed around your family</h1>
            <p class="mt-4 text-slate-600">Pair narrative with imagery via media slots.</p>
            <a href="/contact" class="medca-cta-solid mt-6 inline-flex">Get started</a>
        </div>
        <div class="min-h-[240px] rounded-2xl bg-slate-200/80"></div>
    </div>
</x-blocks.element-wrap>
