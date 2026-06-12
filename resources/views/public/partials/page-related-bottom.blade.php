@php
    $ctx = app(\App\Services\Content\ContentRenderContext::class)->all();
    $links = $internalLinks ?? ($ctx['internalLinks'] ?? []);
@endphp

@if (is_array($links) && $links !== [])
    <div @class([
        'mx-auto w-full max-w-6xl px-4 pb-8 sm:px-6 lg:px-8',
        'pt-0' => ! (isset($page) && $page->usesCanvasLayout()),
        'pt-10 md:pt-12' => isset($page) && $page->usesCanvasLayout(),
    ])>
        <x-public.service-internal-links :links="$links" />
    </div>
@endif
