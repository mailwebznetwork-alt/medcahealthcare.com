<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_removed_mappings', function (Blueprint $table): void {
            $table->id();
            $table->string('mapping_type', 64)->index();
            $table->string('natural_key', 191);
            $table->string('service_code', 191)->index();
            $table->string('pincode', 32)->nullable()->index();
            $table->unsignedBigInteger('removed_by_id')->nullable();
            $table->string('source', 64)->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamp('removed_at')->useCurrent();

            $table->unique(['mapping_type', 'natural_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_removed_mappings');
    }
};
