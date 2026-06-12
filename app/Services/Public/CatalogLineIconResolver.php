<?php

namespace App\Services\Public;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Support\KeyBenefitNormalizer;
use Illuminate\Database\Eloquent\Model;

final class CatalogLineIconResolver
{
    public function __construct(
        private readonly CatalogLineIconMapper $mapper,
    ) {}

    public function forCategory(ServiceCategory $category): string
    {
        if (filled($category->line_icon)) {
            return (string) $category->line_icon;
        }

        return $this->mapper->categoryIcon($category->code, $category->name);
    }

    public function forService(Service $service): string
    {
        if (filled($service->line_icon)) {
            return (string) $service->line_icon;
        }

        return $this->mapper->serviceIcon($service->service_code, $service->title);
    }

    public function forSubService(SubService $subService): string
    {
        if (filled($subService->line_icon)) {
            return (string) $subService->line_icon;
        }

        return $this->mapper->subServiceIcon($subService->sub_service_code, $subService->title);
    }

    /**
     * @return list<array{label: string, icon: string}>
     */
    public function keyBenefitsFor(Model $model): array
    {
        $raw = $model->key_benefits ?? [];

        return KeyBenefitNormalizer::expand(is_array($raw) ? $raw : []);
    }

    public function iconNameFor(Model $model): string
    {
        return match (true) {
            $model instanceof ServiceCategory => $this->forCategory($model),
            $model instanceof Service => $this->forService($model),
            $model instanceof SubService => $this->forSubService($model),
            default => 'circle',
        };
    }
}
