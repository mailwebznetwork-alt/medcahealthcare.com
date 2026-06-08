<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_deletion_tombstones', function (Blueprint $table): void {
            $table->id();
            $table->string('entity_type', 64)->index();
            $table->string('natural_key', 191)->index();
            $table->unsignedBigInteger('deleted_by_id')->nullable();
            $table->timestamp('deleted_at')->useCurrent();

            $table->unique(['entity_type', 'natural_key']);
        });

        if (Schema::hasTable('admin_deletion_tombstones')) {
            DB::table('admin_deletion_tombstones')->updateOrInsert(
                ['entity_type' => 'service', 'natural_key' => 'doctor-home-visit'],
                ['deleted_at' => now(), 'deleted_by_id' => null]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_deletion_tombstones');
    }
};
