<?php

namespace App\Services\Public;

use App\Models\PinCode;
use App\Models\ServiceLocationPage;
use Illuminate\Support\Str;

class PinCodeAreaResolver
{
    public function resolve(string $slug): ?PinCode
    {
        $slug = trim(Str::lower($slug));
        if ($slug === '') {
            return null;
        }

        $pin = PinCode::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->first();

        if ($pin instanceof PinCode) {
            return $pin;
        }

        $byAreaSlug = PinCode::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (PinCode $row): bool => $this->routeSlugFor($row) === $slug);

        if ($byAreaSlug->count() === 1) {
            return $byAreaSlug->first();
        }

        $mapping = ServiceLocationPage::query()
            ->where('location_slug', $slug)
            ->with('pincode')
            ->first();

        $mappedPin = $mapping?->pincode;

        return $mappedPin instanceof PinCode && $mappedPin->is_active ? $mappedPin : null;
    }

    public function routeSlugFor(PinCode $pin): string
    {
        $areaSlug = Str::slug((string) ($pin->area_name ?: $pin->locality ?: ''));
        if ($areaSlug !== '') {
            $collision = PinCode::query()
                ->where('is_active', true)
                ->where('id', '!=', $pin->id)
                ->get()
                ->contains(fn (PinCode $row): bool => Str::slug((string) ($row->area_name ?: $row->locality ?: '')) === $areaSlug);

            if (! $collision) {
                return $areaSlug;
            }
        }

        return filled($pin->slug)
            ? (string) $pin->slug
            : Str::slug(($pin->area_name ?: 'area').'-'.$pin->pincode);
    }

    public function publicUrlFor(PinCode $pin): string
    {
        if (filled($pin->landing_page)) {
            $landing = (string) $pin->landing_page;

            return str_starts_with($landing, ['http://', 'https://'])
                ? $landing
                : url('/'.ltrim($landing, '/'));
        }

        return route('public.locations.area', ['slug' => $this->routeSlugFor($pin)]);
    }
}
