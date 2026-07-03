<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_technical')) {
            return;
        }

        Schema::table('seo_technical', function (Blueprint $table): void {
            if (! Schema::hasColumn('seo_technical', 'indexnow_key')) {
                $table->string('indexnow_key', 128)->nullable()->after('bing_site_verification');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('seo_technical')) {
            return;
        }

        Schema::table('seo_technical', function (Blueprint $table): void {
            if (Schema::hasColumn('seo_technical', 'indexnow_key')) {
                $table->dropColumn('indexnow_key');
            }
        });
    }
};
