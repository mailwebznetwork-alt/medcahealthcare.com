@props(['paginator'])

@if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->hasPages())
    @if ($paginator->currentPage() > 1)
        <link rel="prev" href="{{ $paginator->previousPageUrl() }}">
    @endif
    @if ($paginator->hasMorePages())
        <link rel="next" href="{{ $paginator->nextPageUrl() }}">
    @endif
@endif
