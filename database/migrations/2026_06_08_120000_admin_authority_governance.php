<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columnAfter = [
            'blocks' => 'is_managed',
            'pages' => 'is_active',
            'services' => 'is_active',
            'service_location_pages' => 'is_indexable',
        ];

        foreach ($columnAfter as $table => $afterColumn) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $afterColumn): void {
                if (! Schema::hasColumn($table, 'lifecycle_state')) {
                    $column = $blueprint->string('lifecycle_state', 32)
                        ->default('active')
                        ->index();

                    if (Schema::hasColumn($table, $afterColumn)) {
                        $column->after($afterColumn);
                    }
                }
            });
        }

        if (! Schema::hasTable('automated_write_audits')) {
            Schema::create('automated_write_audits', function (Blueprint $table): void {
                $table->id();
                $table->string('process', 128)->index();
                $table->string('action', 64)->index();
                $table->string('table_name', 64)->nullable()->index();
                $table->unsignedBigInteger('record_id')->nullable()->index();
                $table->string('record_key', 191)->nullable()->index();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('outcome', 32)->default('applied')->index();
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['table_name', 'record_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('automated_write_audits');

        foreach (['blocks', 'pages', 'services', 'service_location_pages'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (Schema::hasColumn($table, 'lifecycle_state')) {
                    $blueprint->dropColumn('lifecycle_state');
                }
            });
        }
    }
};
