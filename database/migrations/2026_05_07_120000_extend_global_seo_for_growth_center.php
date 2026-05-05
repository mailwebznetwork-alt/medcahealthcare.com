<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('business_profiles', 'phone_e164')) {
                $table->string('phone_e164', 32)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('business_profiles', 'country_code')) {
                $table->string('country_code', 8)->nullable()->after('phone_e164');
            }
            if (! Schema::hasColumn('business_profiles', 'street_address')) {
                $table->string('street_address')->nullable()->after('country_code');
            }
            if (! Schema::hasColumn('business_profiles', 'city')) {
                $table->string('city')->nullable()->after('street_address');
            }
            if (! Schema::hasColumn('business_profiles', 'region')) {
                $table->string('region', 64)->nullable()->after('city');
            }
            if (! Schema::hasColumn('business_profiles', 'postal_code')) {
                $table->string('postal_code', 32)->nullable()->after('region');
            }
        });

        Schema::table('seo_entities', function (Blueprint $table): void {
            if (! Schema::hasColumn('seo_entities', 'og_image_url')) {
                $table->string('og_image_url')->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('seo_entities', 'custom_json_ld')) {
                $table->json('custom_json_ld')->nullable()->after('og_image_url');
            }
        });

        Schema::table('seo_technical', function (Blueprint $table): void {
            if (! Schema::hasColumn('seo_technical', 'llm_txt')) {
                $table->text('llm_txt')->nullable()->after('indexable');
            }
            if (! Schema::hasColumn('seo_technical', 'ai_discovery_enabled')) {
                $table->boolean('ai_discovery_enabled')->default(true)->after('llm_txt');
            }
            if (! Schema::hasColumn('seo_technical', 'google_site_verification')) {
                $table->string('google_site_verification')->nullable()->after('ai_discovery_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_technical', function (Blueprint $table): void {
            if (Schema::hasColumn('seo_technical', 'google_site_verification')) {
                $table->dropColumn('google_site_verification');
            }
            if (Schema::hasColumn('seo_technical', 'ai_discovery_enabled')) {
                $table->dropColumn('ai_discovery_enabled');
            }
            if (Schema::hasColumn('seo_technical', 'llm_txt')) {
                $table->dropColumn('llm_txt');
            }
        });

        Schema::table('seo_entities', function (Blueprint $table): void {
            if (Schema::hasColumn('seo_entities', 'custom_json_ld')) {
                $table->dropColumn('custom_json_ld');
            }
            if (Schema::hasColumn('seo_entities', 'og_image_url')) {
                $table->dropColumn('og_image_url');
            }
        });

        Schema::table('business_profiles', function (Blueprint $table): void {
            foreach (['postal_code', 'region', 'city', 'street_address', 'country_code', 'phone_e164'] as $column) {
                if (Schema::hasColumn('business_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
