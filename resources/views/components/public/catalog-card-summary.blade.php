@props([
    'model' => null,
    'summary' => null,
    'limit' => 140,
])

@php
    use App\Models\Service;
    use App\Models\ServiceCategory;
    use App\Models\SubService;
    use App\Services\Public\PublicDisplayNameResolver;

    $resolver = app(PublicDisplayNameResolver::class);

    if (filled($summary)) {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $summary)) ?? '');
    } elseif ($model instanceof Service) {
        $text = $resolver->serviceCardSummary($model, (int) $limit);
    } elseif ($model instanceof ServiceCategory) {
        $text = $resolver->categoryCardSummary($model, (int) $limit);
    } elseif ($model instanceof SubService) {
        $text = $resolver->subServiceCardSummary($model, (int) $limit);
    } else {
        $text = null;
    }
@endphp

@if (filled($text))
    <p {{ $attributes->class(['mt-2 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-600']) }}>{{ $text }}</p>
@endif
