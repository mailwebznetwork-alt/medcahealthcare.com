<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pin_codes', function (Blueprint $table): void {
            if (! Schema::hasColumn('pin_codes', 'state')) {
                $table->string('state', 64)->nullable()->after('city');
            }
            if (! Schema::hasColumn('pin_codes', 'coverage_text')) {
                $table->text('coverage_text')->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('pin_codes', 'emergency_coverage_text')) {
                $table->text('emergency_coverage_text')->nullable()->after('coverage_text');
            }
        });

        if (! Schema::hasTable('pin_code_landmarks')) {
            Schema::create('pin_code_landmarks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('pincode_id')->constrained('pin_codes')->cascadeOnDelete();
                $table->string('name');
                $table->string('landmark_type', 64)->nullable();
                $table->decimal('distance_km', 5, 2)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pin_code_hospitals')) {
            Schema::create('pin_code_hospitals', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('pincode_id')->constrained('pin_codes')->cascadeOnDelete();
                $table->string('name');
                $table->string('address')->nullable();
                $table->string('specialty', 128)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pin_code_location_faqs')) {
            Schema::create('pin_code_location_faqs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('pincode_id')->constrained('pin_codes')->cascadeOnDelete();
                $table->string('question');
                $table->text('answer');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pin_code_nearby_areas')) {
            Schema::create('pin_code_nearby_areas', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('pincode_id')->constrained('pin_codes')->cascadeOnDelete();
                $table->string('area_name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('service_location_pages', function (Blueprint $table): void {
            if (! Schema::hasColumn('service_location_pages', 'quality_snapshot')) {
                $table->json('quality_snapshot')->nullable()->after('city_slug');
            }
            if (! Schema::hasColumn('service_location_pages', 'is_indexable')) {
                $table->boolean('is_indexable')->default(true)->after('quality_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_location_pages', function (Blueprint $table): void {
            if (Schema::hasColumn('service_location_pages', 'is_indexable')) {
                $table->dropColumn('is_indexable');
            }
            if (Schema::hasColumn('service_location_pages', 'quality_snapshot')) {
                $table->dropColumn('quality_snapshot');
            }
        });

        Schema::dropIfExists('pin_code_nearby_areas');
        Schema::dropIfExists('pin_code_location_faqs');
        Schema::dropIfExists('pin_code_hospitals');
        Schema::dropIfExists('pin_code_landmarks');

        Schema::table('pin_codes', function (Blueprint $table): void {
            foreach (['emergency_coverage_text', 'coverage_text', 'state'] as $col) {
                if (Schema::hasColumn('pin_codes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
