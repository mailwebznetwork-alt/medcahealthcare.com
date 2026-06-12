@props([
    'item' => [],
    'kind' => 'service',
])

@php
    use App\Models\Service;
    use App\Models\ServiceCategory;
    use App\Models\SubService;

    $item = is_array($item) ? $item : [];
    $src = $item['image_url'] ?? null;
    $alt = (string) ($item['title'] ?? $item['name'] ?? '');
    $model = null;

    if (! filled($src) && filled($item['code'] ?? null)) {
        $model = match ($kind) {
            'category' => ServiceCategory::query()->where('code', $item['code'])->first(),
            'service' => Service::query()->where('service_code', $item['code'])->first(),
            'sub_service' => SubService::query()->where('sub_service_code', $item['code'])->first(),
            default => null,
        };
    }
@endphp

@if ($model)
    <x-public.catalog-list-card-image :model="$model" />
@elseif (filled($src))
    <x-public.catalog-list-card-image :src="$src" :alt="$alt" />
@endif
