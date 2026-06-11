<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_attribution_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_fingerprint', 128)->unique();
            $table->string('laravel_session_id', 128)->nullable()->index();
            $table->string('landing_page_path', 500)->index();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('pin_code_id')->nullable()->constrained('pin_codes')->nullOnDelete();
            $table->foreignId('service_location_page_id')->nullable()->constrained('service_location_pages')->nullOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->foreignId('sub_service_id')->nullable()->constrained('sub_services')->nullOnDelete();
            $table->string('utm_source', 255)->nullable()->index();
            $table->string('utm_medium', 128)->nullable();
            $table->string('utm_campaign', 255)->nullable()->index();
            $table->string('utm_term', 255)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('gclid', 255)->nullable();
            $table->string('fbclid', 255)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->json('first_touch_json')->nullable();
            $table->json('last_touch_json')->nullable();
            $table->foreignId('converted_lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->index(['service_id', 'last_seen_at']);
            $table->index(['pin_code_id', 'last_seen_at']);
        });

        $this->addAttributionColumns('marketing_click_events');
        $this->addAttributionColumns('lead_intent_events');
        $this->addAttributionColumns('leads');
    }

    public function down(): void
    {
        $this->dropAttributionColumns('leads');
        $this->dropAttributionColumns('lead_intent_events');
        $this->dropAttributionColumns('marketing_click_events');

        Schema::dropIfExists('marketing_attribution_sessions');
    }

    private function addAttributionColumns(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (! Schema::hasColumn($tableName, 'marketing_attribution_session_id')) {
                $table->foreignId('marketing_attribution_session_id')
                    ->nullable()
                    ->constrained('marketing_attribution_sessions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn($tableName, 'page_id')) {
                $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            }

            if (! Schema::hasColumn($tableName, 'service_id')) {
                $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            }

            if ($tableName !== 'leads' && ! Schema::hasColumn($tableName, 'pin_code_id')) {
                $table->foreignId('pin_code_id')->nullable()->constrained('pin_codes')->nullOnDelete();
            }

            if (! Schema::hasColumn($tableName, 'service_location_page_id')) {
                $table->foreignId('service_location_page_id')
                    ->nullable()
                    ->constrained('service_location_pages')
                    ->nullOnDelete();
            }

            if ($tableName === 'marketing_click_events' || $tableName === 'lead_intent_events') {
                $table->index(['service_id', 'occurred_at'], $tableName.'_service_occurred_idx');
            }

            if ($tableName === 'leads') {
                $table->index(['service_id', 'created_at'], 'leads_service_created_idx');
            }
        });
    }

    private function dropAttributionColumns(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (Schema::hasColumn($tableName, 'service_location_page_id')) {
                $table->dropConstrainedForeignId('service_location_page_id');
            }

            if ($tableName !== 'leads' && Schema::hasColumn($tableName, 'pin_code_id')) {
                $table->dropConstrainedForeignId('pin_code_id');
            }

            if (Schema::hasColumn($tableName, 'service_id')) {
                $table->dropConstrainedForeignId('service_id');
            }

            if (Schema::hasColumn($tableName, 'page_id')) {
                $table->dropConstrainedForeignId('page_id');
            }

            if (Schema::hasColumn($tableName, 'marketing_attribution_session_id')) {
                $table->dropConstrainedForeignId('marketing_attribution_session_id');
            }
        });
    }
};
