<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained('vacancies')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 32);
            $table->string('pin_code', 16)->nullable()->index();
            $table->string('city')->nullable();
            $table->text('cover_message')->nullable();
            $table->string('source', 64)->nullable()->index();
            $table->timestamp('whatsapp_clicked_at')->nullable()->index();
            $table->string('pipeline_status', 32)->default('applied')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
