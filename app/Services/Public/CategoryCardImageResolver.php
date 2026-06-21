<?php

namespace App\Services\Public;

use App\Models\ServiceCategory;
use Illuminate\Support\Str;

final class CategoryCardImageResolver
{
    /**
     * @var array<string, string>
     */
    private const ASSET_KEYS = [
        'support-services' => 'support-services',
        'home-consulting-services' => 'home-consulting-services',
        'doctor-visits-at-home' => 'doctor-visits-at-home',
        'medical-equipment' => 'medical-equipment',
        'consulting-services' => 'consulting-services',
        'medical-lab-services' => 'medical-lab-services',
        'ambulance-services' => 'ambulance-services',
        'consulting-school' => 'consulting-school',
    ];

    public function urlFor(ServiceCategory $category): string
    {
        if (filled($category->featured_image)) {
            return Str::startsWith($category->featured_image, ['http://', 'https://'])
                ? (string) $category->featured_image
                : asset('storage/'.$category->featured_image);
        }

        $assetKey = $this->assetKeyFor($category);

        foreach (['webp', 'jpg', 'jpeg', 'png'] as $extension) {
            $relative = "images/category-cards/{$assetKey}.{$extension}";
            if (is_file(public_path($relative))) {
                return asset($relative);
            }
        }

        return asset('images/category-cards/default.jpg');
    }

    public function altFor(ServiceCategory $category, PublicDisplayNameResolver $displayNames): string
    {
        if (filled($category->image_alt)) {
            return (string) $category->image_alt;
        }

        return $displayNames->categoryHeadline($category);
    }

    private function assetKeyFor(ServiceCategory $category): string
    {
        $code = Str::lower((string) $category->code);
        $name = Str::lower((string) $category->name);

        return match (true) {
            str_contains($code, 'support') || str_contains($name, 'support') => self::ASSET_KEYS['support-services'],
            str_contains($code, 'consulting-school') || str_contains($name, 'consulting school') => self::ASSET_KEYS['consulting-school'],
            str_contains($code, 'home-consulting') || str_contains($name, 'core services') => self::ASSET_KEYS['home-consulting-services'],
            str_contains($code, 'doctor') || str_contains($name, 'doctor visit') => self::ASSET_KEYS['doctor-visits-at-home'],
            str_contains($code, 'equipment') || str_contains($name, 'equipment') || str_contains($name, 'supplies') => self::ASSET_KEYS['medical-equipment'],
            str_contains($code, 'physio') || str_contains($code, 'therapy') || str_contains($name, 'physio') || str_contains($name, 'therapy') => self::ASSET_KEYS['consulting-services'],
            str_contains($code, 'lab') || str_contains($name, 'lab test') => self::ASSET_KEYS['medical-lab-services'],
            str_contains($code, 'ambulance') || str_contains($name, 'ambulance') => self::ASSET_KEYS['ambulance-services'],
            default => 'default',
        };
    }
}
