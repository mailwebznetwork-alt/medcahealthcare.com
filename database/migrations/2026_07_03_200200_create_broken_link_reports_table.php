<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('broken_link_reports')) {
            return;
        }

        Schema::create('broken_link_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('status', 32)->default('completed');
            $table->unsignedInteger('pages_scanned')->default(0);
            $table->unsignedInteger('links_checked')->default(0);
            $table->unsignedInteger('broken_count')->default(0);
            $table->json('findings')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broken_link_reports');
    }
};
