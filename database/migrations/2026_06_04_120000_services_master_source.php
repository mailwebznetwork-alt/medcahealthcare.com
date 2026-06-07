<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->json('key_benefits')->nullable()->after('description');
            $table->json('eligibility')->nullable()->after('key_benefits');
            $table->json('process_steps')->nullable()->after('eligibility');
            $table->text('ai_summary')->nullable()->after('process_steps');
            $table->json('gallery_meta')->nullable()->after('gallery');
            $table->json('trust_signals')->nullable()->after('gallery_meta');
            $table->json('optimization_snapshot')->nullable()->after('trust_signals');
        });

        Schema::table('service_seo', function (Blueprint $table): void {
            $table->json('secondary_keywords')->nullable()->after('focus_keywords');
            $table->string('canonical_url', 500)->nullable()->after('search_intent');
            $table->boolean('robots_index')->default(true)->after('canonical_url');
            $table->string('og_title', 255)->nullable()->after('robots_index');
            $table->string('og_description', 500)->nullable()->after('og_title');
            $table->string('og_image', 500)->nullable()->after('og_description');
            $table->string('twitter_card', 40)->nullable()->after('og_image');
            $table->unsignedTinyInteger('seo_score')->default(0)->after('twitter_card');
            $table->unsignedTinyInteger('aeo_score')->default(0)->after('seo_score');
            $table->unsignedTinyInteger('geo_score')->default(0)->after('aeo_score');
            $table->unsignedTinyInteger('schema_health_score')->default(0)->after('geo_score');
            $table->unsignedTinyInteger('content_quality_score')->default(0)->after('schema_health_score');
            $table->unsignedTinyInteger('local_seo_score')->default(0)->after('content_quality_score');
            $table->json('seo_recommendations')->nullable()->after('local_seo_score');
            $table->json('entity_tags')->nullable()->after('seo_recommendations');
            $table->json('geo_entities')->nullable()->after('entity_tags');
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->string('page_category', 32)->nullable()->index()->after('slug');
            $table->string('og_title', 255)->nullable()->after('og_image_alt');
            $table->string('og_description', 500)->nullable()->after('og_title');
            $table->string('twitter_card', 40)->nullable()->after('og_description');
        });

        Schema::create('service_location_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('pincode_id')->constrained('pin_codes')->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('slug', 191);
            $table->timestamps();

            $table->unique(['service_id', 'pincode_id']);
            $table->unique('page_id');
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_location_pages');

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn(['page_category', 'og_title', 'og_description', 'twitter_card']);
        });

        Schema::table('service_seo', function (Blueprint $table): void {
            $table->dropColumn([
                'secondary_keywords',
                'canonical_url',
                'robots_index',
                'og_title',
                'og_description',
                'og_image',
                'twitter_card',
                'seo_score',
                'aeo_score',
                'geo_score',
                'schema_health_score',
                'content_quality_score',
                'local_seo_score',
                'seo_recommendations',
                'entity_tags',
                'geo_entities',
            ]);
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn([
                'key_benefits',
                'eligibility',
                'process_steps',
                'ai_summary',
                'gallery_meta',
                'trust_signals',
                'optimization_snapshot',
            ]);
        });
    }
};
