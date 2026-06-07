<?php

namespace App\Services\Public;

use App\Models\PinCode;
use App\Models\ServiceLocationPage;
use Illuminate\Support\Collection;

class PinCodeCoverageUrlResolver
{
    /**
     * @param  Collection<int, PinCode>  $pins
     * @return array<int, string>
     */
    public function urlsFor(Collection $pins): array
    {
        if ($pins->isEmpty()) {
            return [];
        }

        $mappingsByPin = ServiceLocationPage::query()
            ->whereIn('pincode_id', $pins->pluck('id'))
            ->with(['service', 'page', 'pincode'])
            ->get()
            ->filter(fn (ServiceLocationPage $mapping): bool => $mapping->isPubliclyIndexable())
            ->groupBy('pincode_id');

        $urls = [];
        foreach ($pins as $pin) {
            $urls[$pin->id] = $this->urlFor($pin, $mappingsByPin->get($pin->id));
        }

        return $urls;
    }

    /**
     * @param  Collection<int, ServiceLocationPage>|null  $mappings
     */
    public function urlFor(PinCode $pin, ?Collection $mappings = null): string
    {
        if (filled($pin->landing_page)) {
            $landing = (string) $pin->landing_page;

            return str_starts_with($landing, ['http://', 'https://'])
                ? $landing
                : url('/'.ltrim($landing, '/'));
        }

        $mapping = $mappings?->first();
        if ($mapping === null) {
            $mapping = ServiceLocationPage::query()
                ->where('pincode_id', $pin->id)
                ->with(['service', 'page', 'pincode'])
                ->get()
                ->first(fn (ServiceLocationPage $row): bool => $row->isPubliclyIndexable());
        }

        if ($mapping instanceof ServiceLocationPage) {
            return $mapping->publicUrl();
        }

        return route('location.pincode.select', ['pincode' => $pin->pincode]);
    }
}
