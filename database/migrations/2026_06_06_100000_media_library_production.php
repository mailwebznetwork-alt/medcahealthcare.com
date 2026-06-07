<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->string('thumbnail_path')->nullable()->after('blur_path');
            $table->string('avif_path')->nullable()->after('webp_path');
            $table->unsignedSmallInteger('width')->nullable()->after('file_size');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
            $table->string('mime_type', 128)->nullable()->after('height');
            $table->string('caption')->nullable()->after('title');
            $table->json('tags')->nullable()->after('description');
            $table->string('category', 64)->nullable()->after('tags');
            $table->unsignedTinyInteger('image_seo_score')->nullable()->after('category');
            $table->string('source_module', 64)->nullable()->after('uploaded_by');
        });

        Schema::create('media_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('usable_type');
            $table->unsignedBigInteger('usable_id');
            $table->string('field', 64);
            $table->string('label')->nullable();
            $table->timestamps();

            $table->unique(['media_id', 'usable_type', 'usable_id', 'field'], 'media_usages_unique');
            $table->index(['usable_type', 'usable_id']);
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->foreignId('featured_media_id')->nullable()->after('featured_image')->constrained('media')->nullOnDelete();
            $table->foreignId('icon_media_id')->nullable()->after('icon')->constrained('media')->nullOnDelete();
            $table->json('gallery_media_ids')->nullable()->after('gallery');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('featured_media_id');
            $table->dropConstrainedForeignId('icon_media_id');
            $table->dropColumn('gallery_media_ids');
        });

        Schema::dropIfExists('media_usages');

        Schema::table('media', function (Blueprint $table): void {
            $table->dropColumn([
                'thumbnail_path',
                'avif_path',
                'width',
                'height',
                'mime_type',
                'caption',
                'tags',
                'category',
                'image_seo_score',
                'source_module',
            ]);
        });
    }
};
