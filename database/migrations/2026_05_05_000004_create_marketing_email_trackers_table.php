<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_email_trackers', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('label')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_email_trackers');
    }
};
