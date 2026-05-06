<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('canonical_url')->nullable()->after('meta_description');
            $table->string('robots_meta', 255)->nullable()->after('canonical_url');
            $table->string('og_image')->nullable()->after('robots_meta');
            $table->string('og_image_alt')->nullable()->after('og_image');
            $table->json('hreflang_json')->nullable()->after('og_image_alt');
            $table->json('entity_tags')->nullable()->after('hreflang_json');
            $table->boolean('fact_check_verified')->default(false)->after('entity_tags');
            $table->timestamp('content_reviewed_at')->nullable()->after('fact_check_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'canonical_url',
                'robots_meta',
                'og_image',
                'og_image_alt',
                'hreflang_json',
                'entity_tags',
                'fact_check_verified',
                'content_reviewed_at',
            ]);
        });
    }
};
