<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table): void {
            $table->string('slug', 120)->nullable()->after('code');
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->enum('visibility', ['public', 'private'])->default('public')->after('is_featured');
            $table->boolean('show_on_homepage')->default(false)->after('visibility');
            $table->boolean('show_on_about')->default(false)->after('show_on_homepage');
            $table->boolean('show_on_contact')->default(false)->after('show_on_about');
            $table->foreignId('page_id')->nullable()->after('show_on_contact')->constrained('pages')->nullOnDelete();

            $table->unique('slug');
            $table->index(['is_featured', 'is_active']);
        });

        Schema::create('service_category_seo', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->json('focus_keywords')->nullable();
            $table->json('secondary_keywords')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('robots_index')->default(true);
            $table->string('og_title', 255)->nullable();
            $table->string('og_description', 500)->nullable();
            $table->string('og_image', 500)->nullable();
            $table->string('twitter_card', 40)->nullable();
            $table->text('ai_context')->nullable();
            $table->string('aeo_question', 500)->nullable();
            $table->text('aeo_answer')->nullable();
            $table->unsignedTinyInteger('seo_score')->default(0);
            $table->unsignedTinyInteger('aeo_score')->default(0);
            $table->unsignedTinyInteger('geo_score')->default(0);
            $table->unsignedTinyInteger('ai_discovery_score')->default(0);
            $table->json('geo_signals')->nullable();
            $table->json('aeo_signals')->nullable();
            $table->json('entity_tags')->nullable();
            $table->timestamps();

            $table->unique('service_category_id');
        });

        Schema::create('service_category_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['service_category_id', 'sort_order']);
        });

        Schema::create('service_category_schema', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->string('schema_type', 64)->default('CollectionPage');
            $table->json('schema_json')->nullable();
            $table->timestamps();

            $table->unique('service_category_id');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->boolean('is_top_rated')->default(false)->after('is_featured');
            $table->decimal('avg_rating_cache', 3, 1)->nullable()->after('is_top_rated');
            $table->boolean('show_on_homepage')->default(false)->after('avg_rating_cache');
            $table->boolean('show_on_about')->default(false)->after('show_on_homepage');
            $table->boolean('show_on_contact')->default(false)->after('show_on_about');

            $table->index('is_top_rated');
            $table->index('show_on_homepage');
        });

        Schema::table('sub_services', function (Blueprint $table): void {
            $table->boolean('show_on_homepage')->default(false)->after('is_top_rated');
            $table->boolean('show_on_about')->default(false)->after('show_on_homepage');
            $table->boolean('show_on_contact')->default(false)->after('show_on_about');
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->string('page_source', 32)->default('manual')->after('page_category');
            $table->string('registry_owner', 64)->nullable()->after('page_source');
            $table->json('visibility_flags')->nullable()->after('registry_owner');
        });

        Schema::create('page_registry', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('entity_type', 32);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('registry_key', 191)->unique();
            $table->string('page_category', 32)->nullable();
            $table->string('owner', 64);
            $table->string('source', 32)->default('manual');
            $table->string('public_path', 500)->nullable();
            $table->boolean('is_listed')->default(true);
            $table->json('visibility_snapshot')->nullable();
            $table->json('ownership_snapshot')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['page_category', 'is_listed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_registry');

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn(['page_source', 'registry_owner', 'visibility_flags']);
        });

        Schema::table('sub_services', function (Blueprint $table): void {
            $table->dropColumn(['show_on_homepage', 'show_on_about', 'show_on_contact']);
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropIndex(['is_top_rated']);
            $table->dropIndex(['show_on_homepage']);
            $table->dropColumn(['is_top_rated', 'avg_rating_cache', 'show_on_homepage', 'show_on_about', 'show_on_contact']);
        });

        Schema::dropIfExists('service_category_schema');
        Schema::dropIfExists('service_category_faqs');
        Schema::dropIfExists('service_category_seo');

        Schema::table('service_categories', function (Blueprint $table): void {
            $table->dropForeign(['page_id']);
            $table->dropUnique(['slug']);
            $table->dropIndex(['is_featured', 'is_active']);
            $table->dropColumn([
                'slug',
                'is_featured',
                'visibility',
                'show_on_homepage',
                'show_on_about',
                'show_on_contact',
                'page_id',
            ]);
        });
    }
};
