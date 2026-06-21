<?php

namespace App\Services\Growth;

use App\Models\BusinessProfile;
use App\Models\SeoEntity;
use Illuminate\Support\Facades\Schema;

class SeoEntityResolver
{
    public function __construct(
        private readonly SeoService $seoService,
    ) {}

    public function forCurrentBusiness(): ?SeoEntity
    {
        if (! Schema::hasTable('seo_entities')) {
            return null;
        }

        $profile = BusinessProfile::query()
            ->where('website', config('app.url'))
            ->first();

        if ($profile !== null) {
            $entity = SeoEntity::query()
                ->where('business_profile_id', $profile->id)
                ->first();

            if ($entity !== null) {
                return $entity;
            }
        }

        return SeoEntity::query()->latest('id')->first();
    }

    public function ensureForCurrentBusiness(): SeoEntity
    {
        $existing = $this->forCurrentBusiness();
        if ($existing !== null) {
            return $existing;
        }

        $profile = $this->seoService->ensureBusinessProfile();

        return SeoEntity::query()->firstOrCreate(
            ['business_profile_id' => $profile->id],
            ['organization_name' => config('medca.brand_name', 'Karnataka Diagnostic Centre')]
        );
    }
}
