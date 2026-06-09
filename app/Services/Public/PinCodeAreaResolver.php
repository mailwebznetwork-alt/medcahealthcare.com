<?php

namespace App\Services\Public;

use App\Models\PinCode;
use App\Models\ServiceLocationPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PinCodeAreaResolver
{
    /** @var array<int, string> */
    private array $routeSlugCache = [];

    /** @var array<string, list<int>>|null */
    private ?array $areaSlugIndex = null;

    /** @var array<string, PinCode>|null */
    private ?array $pinsByRouteSlug = null;

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

        $this->ensureRouteSlugMaps();

        if (isset($this->pinsByRouteSlug[$slug])) {
            return PinCode::query()
                ->whereKey($this->pinsByRouteSlug[$slug]->id)
                ->where('is_active', true)
                ->first();
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
        $this->ensureRouteSlugMaps();

        return $this->routeSlugCache[(int) $pin->id]
            ?? $this->computeRouteSlug($pin);
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

    private function ensureRouteSlugMaps(): void
    {
        if ($this->pinsByRouteSlug !== null) {
            return;
        }

        $pins = PinCode::query()
            ->where('is_active', true)
            ->select(['id', 'area_name', 'locality', 'pincode', 'slug'])
            ->orderBy('id')
            ->get();

        $this->areaSlugIndex = [];
        foreach ($pins as $pin) {
            $areaSlug = Str::slug((string) ($pin->area_name ?: $pin->locality ?: ''));
            if ($areaSlug !== '') {
                $this->areaSlugIndex[$areaSlug][] = (int) $pin->id;
            }
        }

        $this->routeSlugCache = [];
        $this->pinsByRouteSlug = [];

        foreach ($pins as $pin) {
            $routeSlug = $this->computeRouteSlug($pin);
            $this->routeSlugCache[(int) $pin->id] = $routeSlug;

            if (! isset($this->pinsByRouteSlug[$routeSlug])) {
                $this->pinsByRouteSlug[$routeSlug] = $pin;
            }
        }
    }

    private function computeRouteSlug(PinCode $pin): string
    {
        $areaSlug = Str::slug((string) ($pin->area_name ?: $pin->locality ?: ''));

        if ($areaSlug !== '') {
            $owners = $this->areaSlugIndex[$areaSlug] ?? [(int) $pin->id];

            if (count($owners) === 1 && $owners[0] === (int) $pin->id) {
                return $areaSlug;
            }
        }

        if (filled($pin->slug)) {
            return (string) $pin->slug;
        }

        return Str::slug(($pin->area_name ?: 'area').'-'.$pin->pincode);
    }
}
