<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('competitors')) {
            try {
                Schema::table('competitors', function (Blueprint $table) {
                    if (! Schema::hasColumn('competitors', 'is_intercept_target')) {
                        $table->boolean('is_intercept_target')->default(false)->after('is_active');
                    }

                    $table->index('name');
                    $table->index('is_active');
                    $table->index('is_intercept_target');
                });
            } catch (Throwable) {
            }
        }

        if (Schema::hasTable('competitor_keywords')) {
            try {
                Schema::table('competitor_keywords', function (Blueprint $table) {
                    $table->index('competitor_id');
                    $table->index('keyword');
                    $table->index(['competitor_id', 'keyword']);
                });
            } catch (Throwable) {
            }
        }

        if (Schema::hasTable('competitor_trackings')) {
            try {
                Schema::table('competitor_trackings', function (Blueprint $table) {
                    $table->index('competitor_keyword_id');
                    $table->index('recorded_date');
                });
            } catch (Throwable) {
            }
        }

        if (Schema::hasTable('competitor_leads')) {
            try {
                Schema::table('competitor_leads', function (Blueprint $table) {
                    $table->index('competitor_keyword_id');
                    $table->index('source');
                    $table->index('created_at');
                });
            } catch (Throwable) {
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('competitors')) {
            try {
                Schema::table('competitors', function (Blueprint $table) {
                    $table->dropIndex(['name']);
                    $table->dropIndex(['is_active']);
                    $table->dropIndex(['is_intercept_target']);
                    if (Schema::hasColumn('competitors', 'is_intercept_target')) {
                        $table->dropColumn('is_intercept_target');
                    }
                });
            } catch (Throwable) {
            }
        }

        if (Schema::hasTable('competitor_keywords')) {
            try {
                Schema::table('competitor_keywords', function (Blueprint $table) {
                    $table->dropIndex(['competitor_id']);
                    $table->dropIndex(['keyword']);
                    $table->dropIndex(['competitor_id', 'keyword']);
                });
            } catch (Throwable) {
            }
        }

        if (Schema::hasTable('competitor_trackings')) {
            try {
                Schema::table('competitor_trackings', function (Blueprint $table) {
                    $table->dropIndex(['competitor_keyword_id']);
                    $table->dropIndex(['recorded_date']);
                });
            } catch (Throwable) {
            }
        }

        if (Schema::hasTable('competitor_leads')) {
            try {
                Schema::table('competitor_leads', function (Blueprint $table) {
                    $table->dropIndex(['competitor_keyword_id']);
                    $table->dropIndex(['source']);
                    $table->dropIndex(['created_at']);
                });
            } catch (Throwable) {
            }
        }
    }
};
