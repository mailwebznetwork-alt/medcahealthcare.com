<?php

namespace App\Services\Operations;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;

/**
 * Generated CMS pages exist only while catalog entities are publicly listed
 * and have active GEO (pincode) coverage.
 */
final class ServiceGeneratedPageEligibility
{
    public static function serviceHasGeoCoverage(Service $service): bool
    {
        return $service->pincodes()->exists();
    }

    public static function categoryHasGeoCoverage(ServiceCategory $category): bool
    {
        return $category->pincodes()->exists();
    }

    public static function serviceMayHavePages(Service $service): bool
    {
        return $service->isListedPublicly() && self::serviceHasGeoCoverage($service);
    }

    public static function categoryMayHavePages(ServiceCategory $category): bool
    {
        return $category->isListedPublicly() && self::categoryHasGeoCoverage($category);
    }

    /**
     * Sub-service pages are catalog-driven (publish/visibility), not GEO-matrix pages.
     */
    public static function subServiceMayHavePages(SubService $sub): bool
    {
        $sub->loadMissing('service');

        if (! $sub->isListedPublicly()) {
            return false;
        }

        return $sub->service === null || $sub->service->isListedPublicly();
    }

    public static function locationMappingMayExist(Service $service, PinCode $pin): bool
    {
        return self::serviceMayHavePages($service)
            && ServiceLocationMatrixPivot::isActive($service, $pin);
    }
}
