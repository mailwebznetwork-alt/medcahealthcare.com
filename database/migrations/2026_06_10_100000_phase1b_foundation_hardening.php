<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('standalone_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('sub_service_code', 120);
            $table->string('title');
            $table->text('short_summary')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->enum('publish_status', ['draft', 'published'])->default('draft');
            $table->enum('visibility', ['public', 'private'])->default('public');
            $table->decimal('avg_rating_cache', 3, 1)->nullable();
            $table->boolean('is_top_rated')->default(false);
            $table->json('custom_fields')->nullable();
            $table->timestamps();

            $table->unique(['service_id', 'sub_service_code']);
            $table->index(['service_id', 'is_active', 'sort_order']);
            $table->index('is_featured');
            $table->index('is_top_rated');
        });

        Schema::create('sub_service_seo', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sub_service_id')->constrained('sub_services')->cascadeOnDelete();
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('h1', 255)->nullable();
            $table->json('focus_keywords')->nullable();
            $table->json('secondary_keywords')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('robots_index')->default(true);
            $table->text('ai_context')->nullable();
            $table->unsignedTinyInteger('seo_score')->default(0);
            $table->unsignedTinyInteger('aeo_score')->default(0);
            $table->unsignedTinyInteger('geo_score')->default(0);
            $table->unsignedTinyInteger('schema_health_score')->default(0);
            $table->json('entity_tags')->nullable();
            $table->json('geo_entities')->nullable();
            $table->timestamps();

            $table->unique('sub_service_id');
        });

        Schema::create('sub_service_schema', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sub_service_id')->constrained('sub_services')->cascadeOnDelete();
            $table->string('schema_type', 64)->default('Service');
            $table->json('schema_json')->nullable();
            $table->timestamps();

            $table->unique('sub_service_id');
        });

        Schema::create('sub_service_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sub_service_id')->constrained('sub_services')->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['sub_service_id', 'sort_order']);
        });

        Schema::table('service_pincodes', function (Blueprint $table): void {
            $table->unsignedInteger('priority')->default(0)->after('pincode_id');
            $table->boolean('is_visible')->default(true)->after('priority');
            $table->boolean('is_featured')->default(false)->after('is_visible');
            $table->text('coverage_notes')->nullable()->after('is_featured');
            $table->json('category_filter_ids')->nullable()->after('coverage_notes');
            $table->date('effective_from')->nullable()->after('category_filter_ids');
            $table->date('effective_until')->nullable()->after('effective_from');

            $table->index(['service_id', 'is_visible', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('service_pincodes', function (Blueprint $table): void {
            $table->dropIndex(['service_id', 'is_visible', 'priority']);
            $table->dropColumn([
                'priority',
                'is_visible',
                'is_featured',
                'coverage_notes',
                'category_filter_ids',
                'effective_from',
                'effective_until',
            ]);
        });

        Schema::dropIfExists('sub_service_faqs');
        Schema::dropIfExists('sub_service_schema');
        Schema::dropIfExists('sub_service_seo');
        Schema::dropIfExists('sub_services');
    }
};
