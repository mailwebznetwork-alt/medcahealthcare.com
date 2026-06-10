<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_categories') && ! Schema::hasColumn('service_categories', 'short_summary')) {
            Schema::table('service_categories', function (Blueprint $table): void {
                $table->text('short_summary')->nullable()->after('description');
                $table->json('key_benefits')->nullable()->after('short_summary');
                $table->json('eligibility')->nullable()->after('key_benefits');
                $table->json('process_steps')->nullable()->after('eligibility');
                $table->text('ai_summary')->nullable()->after('process_steps');
                $table->json('procedures')->nullable()->after('ai_summary');
                $table->json('specialized_care')->nullable()->after('procedures');
                $table->json('shifts')->nullable()->after('specialized_care');
                $table->string('price_range')->nullable()->after('shifts');
                $table->string('featured_image')->nullable()->after('price_range');
                $table->foreignId('featured_media_id')->nullable()->after('featured_image')->constrained('media')->nullOnDelete();
                $table->string('icon')->nullable()->after('featured_media_id');
                $table->foreignId('icon_media_id')->nullable()->after('icon')->constrained('media')->nullOnDelete();
                $table->json('gallery')->nullable()->after('icon_media_id');
                $table->json('gallery_media_ids')->nullable()->after('gallery');
                $table->json('gallery_meta')->nullable()->after('gallery_media_ids');
                $table->string('image_alt')->nullable()->after('gallery_meta');
                $table->json('featured_image_meta')->nullable()->after('image_alt');
                $table->json('trust_signals')->nullable()->after('featured_image_meta');
                $table->json('optimization_snapshot')->nullable()->after('trust_signals');
                $table->json('target_keywords')->nullable()->after('optimization_snapshot');
                $table->json('ai_keywords')->nullable()->after('target_keywords');
                $table->unsignedInteger('quality_score')->default(0)->after('ai_keywords');
                $table->enum('publish_status', ['draft', 'published'])->default('published')->after('quality_score');
                $table->json('custom_fields')->nullable()->after('publish_status');
            });
        }

        if (Schema::hasTable('sub_services') && ! Schema::hasColumn('sub_services', 'key_benefits')) {
            Schema::table('sub_services', function (Blueprint $table): void {
                $table->json('key_benefits')->nullable()->after('description');
                $table->json('eligibility')->nullable()->after('key_benefits');
                $table->json('process_steps')->nullable()->after('eligibility');
                $table->text('ai_summary')->nullable()->after('process_steps');
                $table->json('procedures')->nullable()->after('ai_summary');
                $table->json('specialized_care')->nullable()->after('procedures');
                $table->json('shifts')->nullable()->after('specialized_care');
                $table->string('price_range')->nullable()->after('shifts');
                $table->string('featured_image')->nullable()->after('price_range');
                $table->foreignId('featured_media_id')->nullable()->after('featured_image')->constrained('media')->nullOnDelete();
                $table->string('icon')->nullable()->after('featured_media_id');
                $table->foreignId('icon_media_id')->nullable()->after('icon')->constrained('media')->nullOnDelete();
                $table->json('gallery')->nullable()->after('icon_media_id');
                $table->json('gallery_media_ids')->nullable()->after('gallery');
                $table->json('gallery_meta')->nullable()->after('gallery_media_ids');
                $table->string('image_alt')->nullable()->after('gallery_meta');
                $table->json('featured_image_meta')->nullable()->after('image_alt');
                $table->json('trust_signals')->nullable()->after('featured_image_meta');
                $table->json('optimization_snapshot')->nullable()->after('trust_signals');
                $table->json('target_keywords')->nullable()->after('optimization_snapshot');
                $table->json('ai_keywords')->nullable()->after('target_keywords');
                $table->unsignedInteger('quality_score')->default(0)->after('ai_keywords');
            });
        }

        if (Schema::hasTable('service_category_seo') && ! Schema::hasColumn('service_category_seo', 'h1')) {
            Schema::table('service_category_seo', function (Blueprint $table): void {
                $table->string('h1', 255)->nullable()->after('meta_description');
                $table->json('h2')->nullable()->after('h1');
                $table->json('h3')->nullable()->after('h2');
                $table->string('search_intent', 120)->nullable()->after('ai_context');
                $table->unsignedTinyInteger('schema_health_score')->default(0)->after('geo_score');
                $table->unsignedTinyInteger('content_quality_score')->default(0)->after('schema_health_score');
                $table->unsignedTinyInteger('local_seo_score')->default(0)->after('content_quality_score');
                $table->unsignedTinyInteger('image_seo_score')->default(0)->after('local_seo_score');
                $table->json('seo_recommendations')->nullable()->after('image_seo_score');
                $table->json('geo_entities')->nullable()->after('entity_tags');
            });
        }

        if (Schema::hasTable('sub_service_seo') && ! Schema::hasColumn('sub_service_seo', 'h2')) {
            Schema::table('sub_service_seo', function (Blueprint $table): void {
                $table->json('h2')->nullable()->after('h1');
                $table->json('h3')->nullable()->after('h2');
                $table->string('search_intent', 120)->nullable()->after('ai_context');
                $table->string('og_title', 255)->nullable()->after('robots_index');
                $table->string('og_description', 500)->nullable()->after('og_title');
                $table->string('og_image', 500)->nullable()->after('og_description');
                $table->string('twitter_card', 40)->nullable()->after('og_image');
                $table->unsignedTinyInteger('content_quality_score')->default(0)->after('schema_health_score');
                $table->unsignedTinyInteger('local_seo_score')->default(0)->after('content_quality_score');
                $table->unsignedTinyInteger('ai_discovery_score')->default(0)->after('local_seo_score');
                $table->unsignedTinyInteger('image_seo_score')->default(0)->after('ai_discovery_score');
                $table->json('seo_recommendations')->nullable()->after('image_seo_score');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sub_service_seo') && Schema::hasColumn('sub_service_seo', 'h2')) {
            Schema::table('sub_service_seo', function (Blueprint $table): void {
                $table->dropColumn([
                    'h2', 'h3', 'search_intent', 'og_title', 'og_description', 'og_image', 'twitter_card',
                    'content_quality_score', 'local_seo_score', 'ai_discovery_score', 'image_seo_score', 'seo_recommendations',
                ]);
            });
        }

        if (Schema::hasTable('service_category_seo') && Schema::hasColumn('service_category_seo', 'h1')) {
            Schema::table('service_category_seo', function (Blueprint $table): void {
                $table->dropColumn([
                    'h1', 'h2', 'h3', 'search_intent', 'schema_health_score', 'content_quality_score',
                    'local_seo_score', 'image_seo_score', 'seo_recommendations', 'geo_entities',
                ]);
            });
        }

        if (Schema::hasTable('sub_services') && Schema::hasColumn('sub_services', 'key_benefits')) {
            Schema::table('sub_services', function (Blueprint $table): void {
                $table->dropForeign(['featured_media_id']);
                $table->dropForeign(['icon_media_id']);
                $table->dropColumn([
                    'key_benefits', 'eligibility', 'process_steps', 'ai_summary', 'procedures', 'specialized_care', 'shifts',
                    'price_range', 'featured_image', 'featured_media_id', 'icon', 'icon_media_id', 'gallery', 'gallery_media_ids',
                    'gallery_meta', 'image_alt', 'featured_image_meta', 'trust_signals', 'optimization_snapshot',
                    'target_keywords', 'ai_keywords', 'quality_score',
                ]);
            });
        }

        if (Schema::hasTable('service_categories') && Schema::hasColumn('service_categories', 'short_summary')) {
            Schema::table('service_categories', function (Blueprint $table): void {
                $table->dropForeign(['featured_media_id']);
                $table->dropForeign(['icon_media_id']);
                $table->dropColumn([
                    'short_summary', 'key_benefits', 'eligibility', 'process_steps', 'ai_summary', 'procedures', 'specialized_care', 'shifts',
                    'price_range', 'featured_image', 'featured_media_id', 'icon', 'icon_media_id', 'gallery', 'gallery_media_ids',
                    'gallery_meta', 'image_alt', 'featured_image_meta', 'trust_signals', 'optimization_snapshot',
                    'target_keywords', 'ai_keywords', 'quality_score', 'publish_status', 'custom_fields',
                ]);
            });
        }
    }
};
