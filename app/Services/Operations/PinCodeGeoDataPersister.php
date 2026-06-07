<?php

namespace App\Services\Operations;

use App\Models\PinCode;
use App\Models\PinCodeHospital;
use App\Models\PinCodeLandmark;
use App\Models\PinCodeLocationFaq;
use App\Models\PinCodeNearbyArea;

class PinCodeGeoDataPersister
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function persist(PinCode $pinCode, array $data): void
    {
        $this->syncLandmarks($pinCode, $data['landmarks'] ?? []);
        $this->syncHospitals($pinCode, $data['hospitals'] ?? []);
        $this->syncFaqs($pinCode, $data['location_faqs'] ?? []);
        $this->syncNearbyAreas($pinCode, $data['nearby_areas'] ?? []);
    }

    /**
     * @param  mixed  $rows
     */
    private function syncLandmarks(PinCode $pinCode, mixed $rows): void
    {
        $pinCode->landmarks()->delete();
        foreach ($this->normalizeRows($rows) as $index => $row) {
            if (! filled($row['name'] ?? null)) {
                continue;
            }
            PinCodeLandmark::query()->create([
                'pincode_id' => $pinCode->id,
                'name' => $row['name'],
                'landmark_type' => $row['landmark_type'] ?? null,
                'distance_km' => $row['distance_km'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  mixed  $rows
     */
    private function syncHospitals(PinCode $pinCode, mixed $rows): void
    {
        $pinCode->hospitals()->delete();
        foreach ($this->normalizeRows($rows) as $index => $row) {
            if (! filled($row['name'] ?? null)) {
                continue;
            }
            PinCodeHospital::query()->create([
                'pincode_id' => $pinCode->id,
                'name' => $row['name'],
                'address' => $row['address'] ?? null,
                'specialty' => $row['specialty'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  mixed  $rows
     */
    private function syncFaqs(PinCode $pinCode, mixed $rows): void
    {
        $pinCode->locationFaqs()->delete();
        foreach ($this->normalizeRows($rows) as $index => $row) {
            if (! filled($row['question'] ?? null) || ! filled($row['answer'] ?? null)) {
                continue;
            }
            PinCodeLocationFaq::query()->create([
                'pincode_id' => $pinCode->id,
                'question' => $row['question'],
                'answer' => $row['answer'],
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  mixed  $rows
     */
    private function syncNearbyAreas(PinCode $pinCode, mixed $rows): void
    {
        $pinCode->nearbyAreas()->delete();
        foreach ($this->normalizeRows($rows) as $index => $row) {
            $name = is_array($row) ? ($row['area_name'] ?? $row['name'] ?? null) : $row;
            if (! filled($name)) {
                continue;
            }
            PinCodeNearbyArea::query()->create([
                'pincode_id' => $pinCode->id,
                'area_name' => (string) $name,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, fn ($row) => is_array($row) || is_string($row)));
    }
}
