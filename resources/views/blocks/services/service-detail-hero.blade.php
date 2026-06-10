@php
    use App\Models\Page;
    use App\Support\BlockContent;
    use App\Support\ProductCategoryContext;

    $page = app(\App\Services\Content\ContentRenderContext::class)->all()['currentPage'] ?? null;
    $pageOverrides = ($page instanceof Page && is_array($page->block_overrides_json))
        ? (is_array($page->block_overrides_json['service-detail-hero']['content'] ?? null)
            ? $page->block_overrides_json['service-detail-hero']['content']
            : [])
        : [];
    $pick = static function (string $key, string $fallback) use ($pageOverrides): string {
        $value = $pageOverrides[$key] ?? null;
        if (is_string($value) && trim($value) !== '' && ! BlockContent::isBladePlaceholder(trim($value))) {
            return trim($value);
        }

        return $fallback;
    };
    $isProductCategory = ProductCategoryContext::isService($service);
    $serviceHeadline = app(\App\Services\Public\PublicDisplayNameResolver::class)->serviceHeadline($service);
    if ($isProductCategory) {
        $serviceHeadline = ProductCategoryContext::stripServicesLabel($serviceHeadline);
    }
    $serviceSummary = (string) ($service->short_summary ?? '');
    $eyebrow = $pick('eyebrow', $isProductCategory ? __('Product') : __('Service'));
    $headline = $pick('headline', $serviceHeadline);
    $subheadline = $pick('subheadline', $serviceSummary);
@endphp

<x-public.service-page-hero
    :service="$service"
    :eyebrow="$eyebrow"
    :headline="$headline"
    :subheadline="$subheadline"
    tone="brand"
    data-service-detail-hero
/>
