<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('ga4_measurement_id')->nullable();
            $table->string('ga4_property_id')->nullable()->comment('Numeric GA4 property ID for Data API');
            $table->string('google_ads_aw_id')->nullable()->comment('AW-XXXXXXXXXX');
            $table->string('meta_pixel_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_settings');
    }
};
