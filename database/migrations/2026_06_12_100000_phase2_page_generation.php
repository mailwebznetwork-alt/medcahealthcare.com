<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_services', function (Blueprint $table): void {
            $table->foreignId('page_id')->nullable()->after('standalone_service_id')->constrained('pages')->nullOnDelete();
            $table->json('internal_links_snapshot')->nullable()->after('custom_fields');
        });

        Schema::table('service_categories', function (Blueprint $table): void {
            $table->json('internal_links_snapshot')->nullable()->after('page_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table): void {
            $table->dropColumn('internal_links_snapshot');
        });

        Schema::table('sub_services', function (Blueprint $table): void {
            $table->dropForeign(['page_id']);
            $table->dropColumn(['page_id', 'internal_links_snapshot']);
        });
    }
};
