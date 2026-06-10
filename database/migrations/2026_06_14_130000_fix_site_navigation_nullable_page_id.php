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

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            if (Schema::hasColumn('site_navigation_items', 'page_id')) {
                DB::statement('ALTER TABLE site_navigation_items MODIFY page_id BIGINT UNSIGNED NULL');
            }

            return;
        }

        $pageId = collect(DB::select('PRAGMA table_info(site_navigation_items)'))
            ->firstWhere('name', 'page_id');

        if ($pageId !== null && (int) ($pageId->notnull ?? 1) === 0) {
            return;
        }

        $this->rebuildSqliteNavigationTable();
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
            if (($row['page_id'] ?? null) === null || $row['page_id'] === '') {
                $row['page_id'] = null;
            }
            DB::table('site_navigation_items')->insert($row);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Non-reversible on SQLite without risking data loss.
    }
};
