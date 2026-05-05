<?php

namespace App\Services\Growth;

use App\Models\SeoEntity;
use App\Models\SeoTechnical;

class SeoService
{
    public function saveEntity(array $data): SeoEntity
    {
        $entity = SeoEntity::query()->latest('id')->first();

        if (! $entity instanceof SeoEntity) {
            return SeoEntity::query()->create($data);
        }

        $entity->fill($data)->save();

        return $entity;
    }

    public function saveTechnical(array $data): SeoTechnical
    {
        $technical = SeoTechnical::query()->latest('id')->first();

        if (! $technical instanceof SeoTechnical) {
            return SeoTechnical::query()->create($data);
        }

        $technical->fill($data)->save();

        return $technical;
    }
}
