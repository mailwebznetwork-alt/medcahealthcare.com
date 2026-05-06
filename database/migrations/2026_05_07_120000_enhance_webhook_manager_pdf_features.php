<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_webhooks', function (Blueprint $table) {
            $table->json('mapping_rules')->nullable();
            $table->json('allowed_destination_cidrs')->nullable();
            $table->boolean('verify_ssl')->default(true);
        });

        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->longText('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('outbound_webhooks', function (Blueprint $table) {
            $table->dropColumn(['mapping_rules', 'allowed_destination_cidrs', 'verify_ssl']);
        });

        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropColumn(['request_payload', 'response_payload']);
        });
    }
};
