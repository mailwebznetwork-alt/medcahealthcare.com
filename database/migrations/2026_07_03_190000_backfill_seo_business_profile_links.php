<?php

use App\Models\BusinessProfile;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_profiles')) {
            return;
        }

        $profile = BusinessProfile::query()->where('website', config('app.url'))->first()
            ?? BusinessProfile::query()->latest('id')->first();

        if (! $profile instanceof BusinessProfile) {
            return;
        }

        if (Schema::hasTable('seo_technical')) {
            SeoTechnical::query()
                ->whereNull('business_profile_id')
                ->update(['business_profile_id' => $profile->id]);
        }

        if (Schema::hasTable('seo_entities')) {
            SeoEntity::query()
                ->whereNull('business_profile_id')
                ->update(['business_profile_id' => $profile->id]);
        }
    }

    public function down(): void
    {
        // Non-destructive data repair; no rollback.
    }
};
