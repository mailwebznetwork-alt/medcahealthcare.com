@php
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
@endphp
<x-blocks.element-wrap tone="dark">
    <p class="text-xs uppercase tracking-widest text-white/70">Video hero</p>
    <h1 class="mt-3 text-3xl font-semibold md:text-4xl">See how LetsSee delivers for your business</h1>
    <p class="mt-3 max-w-xl text-white/80">Map the <code class="text-sm">video</code> media slot in Block Studio.</p>
    <div class="mt-6 aspect-video w-full max-w-3xl overflow-hidden rounded-xl bg-slate-800" @if($heroMediaStyle) style="{{ $heroMediaStyle }}" @endif></div>
</x-blocks.element-wrap>
