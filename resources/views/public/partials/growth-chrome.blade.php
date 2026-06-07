@php
    $ctx = app(\App\Services\Content\ContentRenderContext::class)->all();
    $crumbs = $breadcrumbs ?? ($ctx['breadcrumbs'] ?? []);
@endphp

<div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
    <x-public.breadcrumbs :items="$crumbs" />
</div>
