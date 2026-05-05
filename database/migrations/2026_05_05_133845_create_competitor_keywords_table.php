<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('competitor_keywords')) {
            return;
        }

        Schema::create('competitor_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')->constrained('competitors')->cascadeOnDelete();
            $table->string('keyword');
            $table->string('intent_type');
            $table->unsignedInteger('search_volume')->nullable();
            $table->unsignedSmallInteger('difficulty')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('competitor_id');
            $table->index('keyword');
            $table->index(['competitor_id', 'keyword']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_keywords');
    }
};
