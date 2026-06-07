<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_location_pages', function (Blueprint $table): void {
            $table->string('location_slug', 120)->nullable()->after('slug');
            $table->string('city_slug', 80)->nullable()->after('location_slug');
            $table->unique(['service_id', 'location_slug']);
        });

        Schema::table('service_seo', function (Blueprint $table): void {
            $table->unsignedTinyInteger('ai_discovery_score')->default(0)->after('local_seo_score');
            $table->json('entity_graph')->nullable()->after('geo_entities');
            $table->unsignedTinyInteger('image_seo_score')->default(0)->after('ai_discovery_score');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->json('featured_image_meta')->nullable()->after('image_alt');
            $table->json('internal_links_snapshot')->nullable()->after('optimization_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn(['featured_image_meta', 'internal_links_snapshot']);
        });

        Schema::table('service_seo', function (Blueprint $table): void {
            $table->dropColumn(['ai_discovery_score', 'entity_graph', 'image_seo_score']);
        });

        Schema::table('service_location_pages', function (Blueprint $table): void {
            $table->dropUnique(['service_id', 'location_slug']);
            $table->dropColumn(['location_slug', 'city_slug']);
        });
    }
};
