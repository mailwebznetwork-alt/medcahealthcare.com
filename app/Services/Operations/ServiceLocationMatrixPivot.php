<?php

namespace App\Services\Operations;

use App\Models\PinCode;
use App\Models\Service;
use Illuminate\Support\Carbon;

/**
 * Evaluates service_pincodes pivot rules for matrix authority.
 */
final class ServiceLocationMatrixPivot
{
    public static function isActive(Service $service, PinCode $pin): bool
    {
        $service->loadMissing('categories');
        $pin->loadMissing('services');

        $pivot = $pin->pivot ?? $service->pincodes()->where('pin_codes.id', $pin->id)->first()?->pivot;
        if ($pivot === null) {
            return false;
        }

        if (! (bool) ($pivot->is_visible ?? true)) {
            return false;
        }

        if (filled($pivot->effective_from ?? null)) {
            $from = Carbon::parse($pivot->effective_from)->startOfDay();
            if (now()->lt($from)) {
                return false;
            }
        }

        if (filled($pivot->effective_until ?? null)) {
            $until = Carbon::parse($pivot->effective_until)->endOfDay();
            if (now()->gt($until)) {
                return false;
            }
        }

        $filterIds = $pivot->category_filter_ids ?? null;
        if (is_array($filterIds) && $filterIds !== []) {
            $serviceCategoryIds = $service->categories->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $allowed = array_map(static fn (mixed $id): int => (int) $id, $filterIds);
            $intersection = array_intersect($serviceCategoryIds, $allowed);

            if ($intersection === []) {
                return false;
            }
        }

        return true;
    }

    public static function priority(Service $service, PinCode $pin): int
    {
        $pivot = $pin->pivot ?? $service->pincodes()->where('pin_codes.id', $pin->id)->first()?->pivot;

        return (int) ($pivot->priority ?? 0);
    }

    public static function isFeatured(Service $service, PinCode $pin): bool
    {
        $pivot = $pin->pivot ?? $service->pincodes()->where('pin_codes.id', $pin->id)->first()?->pivot;

        return (bool) ($pivot->is_featured ?? false);
    }
}
