<?php

namespace App\Support;

use App\Models\Service;
use App\Models\ServiceCategory;

class ProductCategoryContext
{
    public const CATEGORY_CODE = 'cat-equip';

    public static function isCategory(?ServiceCategory $category): bool
    {
        return $category instanceof ServiceCategory && $category->code === self::CATEGORY_CODE;
    }

    public static function isService(Service $service): bool
    {
        $service->loadMissing('categories');

        return $service->categories->contains(
            fn (ServiceCategory $category) => $category->code === self::CATEGORY_CODE
        );
    }

    public static function stripServicesLabel(string $text): string
    {
        return trim((string) preg_replace('/\s+Services$/i', '', trim($text)));
    }
}
