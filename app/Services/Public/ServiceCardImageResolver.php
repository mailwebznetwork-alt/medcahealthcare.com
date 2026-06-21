<?php

namespace App\Services\Public;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use Illuminate\Support\Str;

final class ServiceCardImageResolver
{
    public function __construct(
        private readonly CategoryCardImageResolver $categoryImages,
    ) {}

    public function urlFor(Service $service): string
    {
        if ($url = $this->urlFromPath($service->featured_image)) {
            return $url;
        }

        if ($url = $this->firstGalleryUrl($service->gallery)) {
            return $url;
        }

        $service->loadMissing('categories');

        $category = $service->categories->first();
        if ($category instanceof ServiceCategory) {
            return $this->categoryImages->urlFor($category);
        }

        return $this->defaultUrl();
    }

    public function altFor(Service $service, PublicDisplayNameResolver $displayNames): string
    {
        if (filled($service->image_alt)) {
            return (string) $service->image_alt;
        }

        return $displayNames->serviceHeadline($service);
    }

    public function urlForSubService(SubService $sub): string
    {
        if ($url = $this->urlFromPath($sub->featured_image)) {
            return $url;
        }

        if ($url = $this->firstGalleryUrl($sub->gallery)) {
            return $url;
        }

        $sub->loadMissing(['service.categories']);

        if ($sub->service instanceof Service) {
            return $this->urlFor($sub->service);
        }

        return $this->defaultUrl();
    }

    public function altForSubService(SubService $sub, PublicDisplayNameResolver $displayNames): string
    {
        if (filled($sub->image_alt)) {
            return (string) $sub->image_alt;
        }

        return $displayNames->subServiceHeadline($sub);
    }

    private function urlFromPath(mixed $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return Str::startsWith((string) $path, ['http://', 'https://'])
            ? (string) $path
            : asset('storage/'.$path);
    }

    private function firstGalleryUrl(mixed $gallery): ?string
    {
        if (! is_array($gallery)) {
            return null;
        }

        foreach ($gallery as $item) {
            if ($url = $this->urlFromPath($item)) {
                return $url;
            }
        }

        return null;
    }

    private function defaultUrl(): string
    {
        return $this->categoryImages->urlFor(
            new ServiceCategory(['code' => 'default', 'name' => 'Healthcare Career Consultancy'])
        );
    }
}
