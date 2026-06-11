<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_pincodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->foreignId('pincode_id')->constrained('pin_codes')->cascadeOnDelete();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['service_category_id', 'pincode_id']);
            $table->index(['service_category_id', 'is_visible', 'priority']);
        });

        Schema::table('service_category_map', function (Blueprint $table): void {
            $table->boolean('is_primary')->default(false)->after('service_category_id');
            $table->index(['service_id', 'is_primary']);
        });

        Schema::table('service_pincodes', function (Blueprint $table): void {
            $table->string('pin_source', 32)->default('manual')->after('pincode_id');
            $table->index(['service_id', 'pin_source']);
        });

        Schema::create('sub_service_pincode_exclusions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sub_service_id')->constrained('sub_services')->cascadeOnDelete();
            $table->foreignId('pincode_id')->constrained('pin_codes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sub_service_id', 'pincode_id']);
        });

        $this->backfillPrimaryCategories();
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_service_pincode_exclusions');

        Schema::table('service_pincodes', function (Blueprint $table): void {
            $table->dropIndex(['service_id', 'pin_source']);
            $table->dropColumn('pin_source');
        });

        Schema::table('service_category_map', function (Blueprint $table): void {
            $table->dropIndex(['service_id', 'is_primary']);
            $table->dropColumn('is_primary');
        });

        Schema::dropIfExists('category_pincodes');
    }

    private function backfillPrimaryCategories(): void
    {
        $serviceIds = DB::table('service_category_map')
            ->distinct()
            ->pluck('service_id');

        foreach ($serviceIds as $serviceId) {
            $firstMapId = DB::table('service_category_map')
                ->where('service_id', $serviceId)
                ->orderBy('id')
                ->value('id');

            if ($firstMapId !== null) {
                DB::table('service_category_map')
                    ->where('id', $firstMapId)
                    ->update(['is_primary' => true]);
            }
        }
    }
};
