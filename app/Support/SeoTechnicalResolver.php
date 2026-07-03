<?php

namespace App\Support;

use App\Models\BusinessProfile;
use App\Models\SeoTechnical;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves Growth Center technical SEO for the active public site.
 */
final class SeoTechnicalResolver
{
    public static function currentProfile(): ?BusinessProfile
    {
        if (! Schema::hasTable('business_profiles')) {
            return null;
        }

        return BusinessProfile::query()->where('website', config('app.url'))->first()
            ?? BusinessProfile::query()->latest('id')->first();
    }

    public static function forCurrentSite(bool $repairOrphan = true): ?SeoTechnical
    {
        if (! Schema::hasTable('seo_technical')) {
            return null;
        }

        $profile = self::currentProfile();

        if ($profile instanceof BusinessProfile) {
            $linked = SeoTechnical::query()->where('business_profile_id', $profile->id)->first();
            if ($linked instanceof SeoTechnical) {
                return $linked;
            }
        }

        $orphan = SeoTechnical::query()
            ->whereNull('business_profile_id')
            ->latest('id')
            ->first();

        if ($orphan instanceof SeoTechnical) {
            if ($repairOrphan && $profile instanceof BusinessProfile) {
                $orphan->forceFill(['business_profile_id' => $profile->id])->saveQuietly();
            }

            return $orphan;
        }

        return SeoTechnical::query()->latest('id')->first();
    }
}
