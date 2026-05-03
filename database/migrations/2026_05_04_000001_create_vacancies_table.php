<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('department')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('area')->nullable();
            $table->string('pin_code', 16)->nullable()->index();
            $table->string('country_code', 4)->default('IN');
            $table->string('employment_type', 32)->index();
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->string('salary_currency', 8)->default('INR');
            $table->date('closing_date')->nullable()->index();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->longText('requirements')->nullable();
            $table->string('whatsapp_apply_url')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('focus_keywords')->nullable();
            $table->text('ai_context')->nullable();
            $table->json('schema_json')->nullable();
            $table->string('visibility', 16)->default('public')->index();
            $table->string('workflow_status', 24)->default('draft')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};
