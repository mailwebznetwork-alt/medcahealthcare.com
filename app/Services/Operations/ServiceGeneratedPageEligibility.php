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

    public static function subServiceMayHavePages(SubService $sub): bool
    {
        $sub->loadMissing('service');

        if (! $sub->isListedPublicly()) {
            return false;
        }

        return $sub->service === null || self::serviceMayHavePages($sub->service);
    }

    public static function locationMappingMayExist(Service $service, PinCode $pin): bool
    {
        return self::serviceMayHavePages($service)
            && ServiceLocationMatrixPivot::isActive($service, $pin);
    }
}
