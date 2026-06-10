<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_catalog_states', function (Blueprint $table): void {
            $table->id();
            $table->string('zone', 32)->unique();
            $table->json('exclusions')->nullable();
            $table->json('manual_children')->nullable();
            $table->json('sibling_orders')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_catalog_states');
    }
};
