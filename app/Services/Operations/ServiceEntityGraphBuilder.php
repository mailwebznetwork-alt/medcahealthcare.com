<?php

namespace App\Services\Operations;

use App\Models\Service;

class ServiceEntityGraphBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Service $service): array
    {
        $service->loadMissing(['seo', 'pincodes', 'locationPages.pincode', 'categories']);

        $brand = config('medca.brand_name', 'MarkOnMinds');
        $nodes = [
            ['id' => 'org:medca', 'type' => 'Organization', 'name' => $brand],
            ['id' => 'service:'.$service->service_code, 'type' => 'Service', 'name' => $service->title],
        ];
        $edges = [
            ['from' => 'org:medca', 'to' => 'service:'.$service->service_code, 'relation' => 'offers'],
        ];

        foreach ($service->pincodes as $pin) {
            $locId = 'location:'.$service->service_code.':'.$pin->pincode;
            $nodes[] = [
                'id' => $locId,
                'type' => 'Location',
                'name' => $pin->area_name ?: $pin->locality ?: $pin->pincode,
                'pincode' => $pin->pincode,
                'city' => $pin->city,
            ];
            $edges[] = ['from' => 'service:'.$service->service_code, 'to' => $locId, 'relation' => 'availableIn'];
            if (filled($pin->city)) {
                $cityId = 'city:'.strtolower(str_replace(' ', '-', $pin->city));
                $nodes[] = ['id' => $cityId, 'type' => 'City', 'name' => $pin->city];
                $edges[] = ['from' => $locId, 'to' => $cityId, 'relation' => 'inCity'];
            }
        }

        foreach (is_array($service->seo?->entity_tags) ? $service->seo->entity_tags : [] as $tag) {
            $nodes[] = ['id' => 'specialty:'.md5($tag), 'type' => 'MedicalSpecialty', 'name' => $tag];
            $edges[] = ['from' => 'service:'.$service->service_code, 'to' => 'specialty:'.md5($tag), 'relation' => 'relatedTo'];
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function persist(Service $service): void
    {
        $graph = $this->build($service);
        $service->seo()->updateOrCreate(
            ['service_id' => $service->id],
            ['entity_graph' => $graph]
        );
    }
}
