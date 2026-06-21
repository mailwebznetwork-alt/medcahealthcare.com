@props(['links' => []])

@php
    use App\Models\Service;
    use App\Models\ServiceCategory;
    use App\Services\Content\ContentRenderContext;
    use App\Support\ProductCategoryContext;

    $renderContext = app(ContentRenderContext::class)->all();
    $contextService = $renderContext['service'] ?? null;
    $contextCategory = $renderContext['category'] ?? $renderContext['serviceCategory'] ?? null;
    $isProductCategory = ProductCategoryContext::isCategory($contextCategory instanceof ServiceCategory ? $contextCategory : null)
        || ($contextService instanceof Service && ProductCategoryContext::isService($contextService));

    $formatTitle = static function (?string $title) use ($isProductCategory): string {
        $title = (string) ($title ?? '');

        return $isProductCategory ? ProductCategoryContext::stripServicesLabel($title) : $title;
    };

    $parentService = is_array($links['parent_service'] ?? null) ? $links['parent_service'] : null;
    $relatedCategories = is_array($links['related_categories'] ?? null) ? $links['related_categories'] : [];
    $relatedSubServices = is_array($links['related_sub_services'] ?? null) ? $links['related_sub_services'] : [];
    $relatedServices = is_array($links['related_services'] ?? null) ? $links['related_services'] : [];
    $relatedLocations = is_array($links['related_locations'] ?? null) ? $links['related_locations'] : [];
    $relatedPages = is_array($links['related_pages'] ?? null) ? $links['related_pages'] : [];

    if (is_array($parentService) && filled($parentService['url'] ?? null)) {
        $relatedPages = array_merge(
            [['title' => __('Parent service: :title', ['title' => $parentService['title'] ?? '']), 'url' => $parentService['url']]],
            $relatedPages
        );
    }
@endphp

@if ($relatedCategories !== [] || $relatedSubServices !== [] || $relatedServices !== [] || $relatedLocations !== [] || $relatedPages !== [])
<section class="mt-4 space-y-8 rounded-xl border border-slate-200 bg-slate-50 p-6 md:p-8">
    <h2 class="text-xl font-semibold text-slate-900">{{ __('Related') }}</h2>

    @if ($relatedCategories !== [])
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-slate-700">{{ __('Service Category') }}</h3>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($relatedCategories as $item)
                    <li>
                        <a href="{{ $item['url'] ?? '#' }}" class="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-medca-primary/30 hover:shadow-md">
                            <x-public.internal-link-card-image :item="$item" kind="category" />
                            <div class="flex flex-1 flex-col p-5">
                                <span class="text-base font-semibold text-slate-900 group-hover:text-medca-primary">{{ $item['name'] ?? $item['title'] ?? '' }}</span>
                                <x-public.catalog-card-summary :summary="$item['summary'] ?? null" />
                                <span class="mt-3 text-sm font-semibold text-medca-primary">{{ __('View category') }} →</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($relatedSubServices !== [])
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-slate-700">{{ __('Services Included') }}</h3>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($relatedSubServices as $item)
                    <li>
                        <a href="{{ $item['url'] ?? '#' }}" class="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-medca-primary/30 hover:shadow-md">
                            <x-public.internal-link-card-image :item="$item" kind="sub_service" />
                            <div class="flex flex-1 flex-col p-5">
                                <span class="text-base font-semibold text-slate-900 group-hover:text-medca-primary">{{ $item['title'] ?? '' }}</span>
                                <x-public.catalog-card-summary :summary="$item['summary'] ?? null" />
                                <span class="mt-3 text-sm font-semibold text-medca-primary">{{ __('View details') }} →</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($relatedServices !== [])
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-slate-700">{{ __('Related Care Services') }}</h3>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($relatedServices as $item)
                    <li>
                        <a href="{{ $item['url'] ?? '#' }}" class="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-medca-primary/30 hover:shadow-md">
                            <x-public.internal-link-card-image :item="$item" kind="service" />
                            <div class="flex flex-1 flex-col p-5">
                                <span class="text-base font-semibold text-slate-900 group-hover:text-medca-primary">{{ $formatTitle($item['title'] ?? '') }}</span>
                                <x-public.catalog-card-summary :summary="$item['summary'] ?? null" />
                                <span class="mt-3 text-sm font-semibold text-medca-primary">{{ __('View details') }} →</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($relatedLocations !== [])
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-slate-700">{{ __('Countries & States We Serve') }}</h3>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($relatedLocations as $item)
                    <li>
                        <a href="{{ $item['url'] ?? '#' }}" class="group flex h-full flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-medca-primary/30 hover:shadow-md">
                            <span class="text-base font-semibold text-slate-900 group-hover:text-medca-primary">{{ $item['title'] ?? '' }}</span>
                            <span class="mt-3 text-sm font-semibold text-medca-primary">{{ __('View location') }} →</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($relatedPages !== [])
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-slate-700">{{ __('Explore More') }}</h3>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($relatedPages as $item)
                    <li>
                        <a href="{{ $item['url'] ?? '#' }}" class="group flex h-full flex-col rounded-xl border border-dashed border-slate-300 bg-white p-5 transition hover:border-medca-primary/30">
                            <span class="text-base font-semibold text-slate-900 group-hover:text-medca-primary">{{ $item['title'] ?? '' }}</span>
                            <span class="mt-3 text-sm font-semibold text-medca-primary">{{ __('View more') }} →</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
@endif
