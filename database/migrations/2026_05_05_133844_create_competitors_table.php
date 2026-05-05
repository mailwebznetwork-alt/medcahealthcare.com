<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('competitors')) {
            return;
        }

        Schema::create('competitors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_intercept_target')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('is_active');
            $table->index('is_intercept_target');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitors');
    }
};
