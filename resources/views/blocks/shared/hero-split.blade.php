@php
    $media = is_array($blockMedia ?? null) ? $blockMedia : [];
    $section = is_array($blockSection ?? null) ? $blockSection : [];
    $preset = ($section['layout_preset'] ?? null) ?: 'left-image-right-content';
    $imagePath = \App\Support\BlockMediaUrl::first($media, 'image', 'desktop_image', 'fallback_image');
    $imageRef = is_array($blockMediaRefs ?? null) ? ($blockMediaRefs['image'] ?? $blockMediaRefs['desktop_image'] ?? null) : null;
@endphp
<x-blocks.element-wrap tone="light" :preset="$preset" :section="$section">
    <div @class(['medca-layout-'.$preset => true, 'grid items-center gap-10 lg:grid-cols-2' => $preset === null])>
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-medca-primary">Split layout</p>
            <h1 class="mt-3 text-3xl font-semibold md:text-4xl">Care designed around your family</h1>
            <p class="mt-4 text-slate-600">Pair narrative with imagery via media slots.</p>
            <a href="/contact" class="medca-cta-solid mt-6 inline-flex">Get started</a>
        </div>
        @if ($imagePath || $imageRef)
            <x-public.responsive-media :path="$imagePath" :media-id="$imageRef" :preset="$preset" :section="$section" class="min-h-[240px]" />
        @else
            <div class="min-h-[240px] rounded-2xl bg-slate-200/80"></div>
        @endif
    </div>
</x-blocks.element-wrap>
