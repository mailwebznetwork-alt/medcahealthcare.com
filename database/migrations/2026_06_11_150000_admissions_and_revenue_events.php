<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('pin_code_id')->nullable()->constrained('pin_codes')->nullOnDelete();
            $table->foreignId('service_location_page_id')->nullable()->constrained('service_location_pages')->nullOnDelete();
            $table->foreignId('marketing_attribution_session_id')->nullable()->constrained('marketing_attribution_sessions')->nullOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->string('patient_name', 255);
            $table->string('patient_phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('admitted_at')->nullable()->index();
            $table->timestamp('discharged_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['service_id', 'admitted_at']);
            $table->index(['pin_code_id', 'admitted_at']);
        });

        Schema::create('revenue_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('pin_code_id')->nullable()->constrained('pin_codes')->nullOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->foreignId('marketing_attribution_session_id')->nullable()->constrained('marketing_attribution_sessions')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('label', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at')->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['service_id', 'recorded_at']);
            $table->index(['pin_code_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_events');
        Schema::dropIfExists('admissions');
    }
};
