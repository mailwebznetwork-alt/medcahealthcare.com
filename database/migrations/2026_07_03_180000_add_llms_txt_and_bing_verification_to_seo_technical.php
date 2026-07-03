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
            if (! Schema::hasColumn('seo_technical', 'llms_txt')) {
                $table->longText('llms_txt')->nullable()->after('llm_txt');
            }
            if (! Schema::hasColumn('seo_technical', 'bing_site_verification')) {
                $table->string('bing_site_verification', 128)->nullable()->after('google_site_verification');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('seo_technical')) {
            return;
        }

        Schema::table('seo_technical', function (Blueprint $table): void {
            if (Schema::hasColumn('seo_technical', 'llms_txt')) {
                $table->dropColumn('llms_txt');
            }
            if (Schema::hasColumn('seo_technical', 'bing_site_verification')) {
                $table->dropColumn('bing_site_verification');
            }
        });
    }
};
