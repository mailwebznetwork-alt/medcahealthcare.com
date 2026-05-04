<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_communication_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 24);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->json('metrics');
            $table->timestamps();

            $table->index(['channel', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_communication_snapshots');
    }
};
