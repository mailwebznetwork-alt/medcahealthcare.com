<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_tracking_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('exotel')->index();
            $table->string('exophone_sid', 64)->nullable()->index();
            $table->string('phone_number', 32)->index();
            $table->string('phone_normalized', 20)->index();
            $table->string('label', 128)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'phone_normalized']);
        });

        Schema::create('call_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('exotel')->index();
            $table->string('provider_call_sid', 64)->index();
            $table->string('idempotency_key', 191)->unique();
            $table->string('provider_event_type', 32)->nullable()->index();
            $table->string('status', 32)->index();
            $table->string('raw_status', 32)->nullable();
            $table->string('direction', 32)->nullable()->index();
            $table->string('caller_number', 32)->nullable()->index();
            $table->string('caller_normalized', 20)->nullable()->index();
            $table->string('called_number', 32)->nullable();
            $table->foreignId('call_tracking_number_id')->nullable()->constrained('call_tracking_numbers')->nullOnDelete();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('ended_at')->nullable();
            $table->string('recording_url', 500)->nullable();
            $table->string('custom_field', 255)->nullable();
            $table->json('raw_payload')->nullable();
            $table->foreignId('marketing_attribution_session_id')->nullable()->constrained('marketing_attribution_sessions')->nullOnDelete();
            $table->foreignId('marketing_click_event_id')->nullable()->constrained('marketing_click_events')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('pin_code_id')->nullable()->constrained('pin_codes')->nullOnDelete();
            $table->foreignId('service_location_page_id')->nullable()->constrained('service_location_pages')->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['service_id', 'occurred_at']);
            $table->index(['pin_code_id', 'occurred_at']);
            $table->index(['status', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_events');
        Schema::dropIfExists('call_tracking_numbers');
    }
};
