<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('integration_accounts')) {
            return;
        }

        Schema::create('integration_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->string('label');
            $table->string('account_identifier')->nullable();
            $table->json('credentials')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['integration_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_accounts');
    }
};
