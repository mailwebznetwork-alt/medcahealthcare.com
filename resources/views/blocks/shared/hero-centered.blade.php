@php
    $heroMediaStyle = \App\Support\BlockMediaUrl::heroBackgroundStyle(is_array($blockMedia ?? null) ? $blockMedia : []);
 @endphp
<x-public.hero class="medca-hero-gradient text-white" style="{{ $heroMediaStyle }}"><div class="mx-auto max-w-6xl px-4 py-16 md:py-24"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">Healthcare career guidance</p><h1 class="mt-4 text-center text-3xl font-semibold md:text-5xl">Centered hero headline</h1><p class="mx-auto mt-4 max-w-2xl text-center text-white/85">Healthcare career guidance for global opportunities — customize in Block Studio.</p><div class="mt-8 flex flex-wrap justify-center gap-3"><a href="/contact" class="medca-cta-on-hero">Book a consultation</a></div></div></x-public.hero>
