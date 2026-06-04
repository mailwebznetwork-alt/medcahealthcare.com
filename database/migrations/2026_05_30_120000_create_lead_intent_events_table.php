<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_intent_events', function (Blueprint $table) {
            $table->id();
            $table->string('intent_type', 64)->index();
            $table->string('channel', 32)->index();
            $table->string('attribution_bucket', 32)->index();
            $table->string('source', 128)->nullable()->index();
            $table->string('medium', 128)->nullable();
            $table->string('campaign', 255)->nullable()->index();
            $table->string('landing_page', 500)->nullable()->index();
            $table->string('service_page', 500)->nullable();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('marketing_click_event_id')->nullable()->constrained('marketing_click_events')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->string('session_fingerprint', 64)->nullable()->index();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['channel', 'occurred_at']);
            $table->index(['attribution_bucket', 'occurred_at']);
            $table->unique('marketing_click_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_intent_events');
    }
};
