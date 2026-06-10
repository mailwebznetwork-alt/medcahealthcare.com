<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return;
        }

        Schema::table('site_navigation_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_navigation_items', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('site_navigation_items')
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('site_navigation_items', 'item_type')) {
                $table->string('item_type', 32)->default('page')->after('zone');
            }

            if (! Schema::hasColumn('site_navigation_items', 'service_category_id')) {
                $table->foreignId('service_category_id')
                    ->nullable()
                    ->after('page_id')
                    ->constrained('service_categories')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('site_navigation_items', 'service_id')) {
                $table->foreignId('service_id')
                    ->nullable()
                    ->after('service_category_id')
                    ->constrained('services')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('site_navigation_items', 'sub_service_id')) {
                $table->foreignId('sub_service_id')
                    ->nullable()
                    ->after('service_id')
                    ->constrained('sub_services')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('site_navigation_items', 'custom_url')) {
                $table->string('custom_url', 500)->nullable()->after('sub_service_id');
            }

            if (! Schema::hasColumn('site_navigation_items', 'title')) {
                $table->string('title', 255)->nullable()->after('custom_url');
            }
        });

        if (Schema::hasColumn('site_navigation_items', 'page_id')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE site_navigation_items MODIFY page_id BIGINT UNSIGNED NULL');
            } elseif ($driver === 'sqlite') {
                $this->rebuildSqliteNavigationTable();
            }
        }

        try {
            Schema::table('site_navigation_items', function (Blueprint $table): void {
                $table->dropUnique(['zone', 'page_id']);
            });
        } catch (\Throwable) {
            // Index may not exist on all environments.
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('site_navigation_items', function (Blueprint $table): void {
                $table->index(['zone', 'parent_id', 'sort_order'], 'site_nav_zone_parent_sort_idx');
            });
        }
    }

    private function rebuildSqliteNavigationTable(): void
    {
        Schema::disableForeignKeyConstraints();

        $rows = DB::table('site_navigation_items')->get()->map(fn ($row) => (array) $row)->all();

        Schema::drop('site_navigation_items');

        Schema::create('site_navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('zone', 16);
            $table->string('item_type', 32)->default('page');
            $table->unsignedBigInteger('page_id')->nullable();
            $table->unsignedBigInteger('service_category_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('sub_service_id')->nullable();
            $table->string('custom_url', 500)->nullable();
            $table->string('title', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('custom_label', 255)->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('site_navigation_items')->cascadeOnDelete();
            $table->foreign('page_id')->references('id')->on('pages')->cascadeOnDelete();
            $table->foreign('service_category_id')->references('id')->on('service_categories')->nullOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
            $table->foreign('sub_service_id')->references('id')->on('sub_services')->nullOnDelete();
            $table->index(['zone', 'parent_id', 'sort_order'], 'site_nav_zone_parent_sort_idx');
        });

        foreach ($rows as $row) {
            unset($row['id']);
            DB::table('site_navigation_items')->insert($row);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_navigation_items')) {
            return;
        }

        Schema::table('site_navigation_items', function (Blueprint $table): void {
            if (Schema::hasColumn('site_navigation_items', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            foreach (['item_type', 'service_category_id', 'service_id', 'sub_service_id', 'custom_url', 'title'] as $col) {
                if (Schema::hasColumn('site_navigation_items', $col)) {
                    if (in_array($col, ['service_category_id', 'service_id', 'sub_service_id'], true)) {
                        $table->dropForeign([$col]);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
