@props([
    'model' => null,
    'src' => null,
    'alt' => null,
])

@php
    use App\Models\Service;
    use App\Models\ServiceCategory;
    use App\Models\SubService;
    use App\Services\Public\CategoryCardImageResolver;
    use App\Services\Public\PublicDisplayNameResolver;
    use App\Services\Public\ServiceCardImageResolver;

    $displayNames = app(PublicDisplayNameResolver::class);

    if (filled($src)) {
        $imageSrc = (string) $src;
        $imageAlt = filled($alt) ? (string) $alt : '';
    } elseif ($model instanceof ServiceCategory) {
        $resolver = app(CategoryCardImageResolver::class);
        $imageSrc = $resolver->urlFor($model);
        $imageAlt = $resolver->altFor($model, $displayNames);
    } elseif ($model instanceof Service) {
        $resolver = app(ServiceCardImageResolver::class);
        $imageSrc = $resolver->urlFor($model);
        $imageAlt = $resolver->altFor($model, $displayNames);
    } elseif ($model instanceof SubService) {
        $resolver = app(ServiceCardImageResolver::class);
        $imageSrc = $resolver->urlForSubService($model);
        $imageAlt = $resolver->altForSubService($model, $displayNames);
    } else {
        return;
    }
@endphp

<div {{ $attributes->class(['catalog-list-card__media']) }}>
    <img
        src="{{ $imageSrc }}"
        alt="{{ $imageAlt }}"
        class="catalog-list-card__image"
        loading="lazy"
        decoding="async"
    />
</div>
