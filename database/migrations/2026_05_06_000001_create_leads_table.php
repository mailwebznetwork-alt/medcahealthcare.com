<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('phone', 32);
            $table->string('phone_normalized', 32)->index();
            $table->string('email')->nullable();
            $table->string('service');
            $table->text('message')->nullable();

            $table->string('source', 32)->index();
            $table->string('campaign')->nullable()->index();

            $table->foreignId('pin_code_id')->nullable()->constrained('pin_codes')->nullOnDelete();

            $table->string('status', 32)->index();

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('follow_up_date')->nullable()->index();

            $table->timestamps();

            $table->index('phone');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
