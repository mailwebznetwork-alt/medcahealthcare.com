<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_profiles')) {
            Schema::create('business_profiles', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone', 32)->nullable();
                $table->string('website')->nullable();
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('page_seo')) {
            Schema::create('page_seo', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_profile_id')->nullable()->constrained('business_profiles')->nullOnDelete();
                $table->string('page_slug')->unique();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->json('schema_json')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('page_elements')) {
            Schema::create('page_elements', function (Blueprint $table): void {
                $table->id();
                $table->string('page_slug');
                $table->string('section');
                $table->string('key');
                $table->longText('value')->nullable();
                $table->string('type', 40)->default('text');
                $table->timestamps();

                $table->index(['page_slug', 'section']);
            });
        }

        Schema::table('seo_entities', function (Blueprint $table): void {
            if (! Schema::hasColumn('seo_entities', 'business_profile_id')) {
                $table->foreignId('business_profile_id')->nullable()->after('id')->constrained('business_profiles')->nullOnDelete();
            }
            if (! Schema::hasColumn('seo_entities', 'logo')) {
                $table->string('logo')->nullable()->after('organization_name');
            }
            if (! Schema::hasColumn('seo_entities', 'same_as')) {
                $table->json('same_as')->nullable()->after('logo');
            }
            if (! Schema::hasColumn('seo_entities', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('same_as');
            }
            if (! Schema::hasColumn('seo_entities', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
        });

        Schema::table('seo_technical', function (Blueprint $table): void {
            if (! Schema::hasColumn('seo_technical', 'business_profile_id')) {
                $table->foreignId('business_profile_id')->nullable()->after('id')->constrained('business_profiles')->nullOnDelete();
            }
            if (! Schema::hasColumn('seo_technical', 'robots_txt')) {
                $table->text('robots_txt')->nullable()->after('robots_content');
            }
            if (! Schema::hasColumn('seo_technical', 'canonical_url')) {
                $table->string('canonical_url')->nullable()->after('sitemap_enabled');
            }
            if (! Schema::hasColumn('seo_technical', 'indexable')) {
                $table->boolean('indexable')->default(true)->after('canonical_url');
            }
        });

        Schema::table('seo_ai_signals', function (Blueprint $table): void {
            if (! Schema::hasColumn('seo_ai_signals', 'business_profile_id')) {
                $table->foreignId('business_profile_id')->nullable()->after('id')->constrained('business_profiles')->nullOnDelete();
            }
        });

        Schema::table('geo_locations', function (Blueprint $table): void {
            if (! Schema::hasColumn('geo_locations', 'business_profile_id')) {
                $table->foreignId('business_profile_id')->nullable()->after('id')->constrained('business_profiles')->nullOnDelete();
            }
        });

        Schema::table('pincodes', function (Blueprint $table): void {
            if (! Schema::hasColumn('pincodes', 'business_profile_id')) {
                $table->foreignId('business_profile_id')->nullable()->after('id')->constrained('business_profiles')->nullOnDelete();
            }
            if (! Schema::hasColumn('pincodes', 'pincode')) {
                $table->string('pincode', 20)->nullable()->after('business_profile_id');
            }
            if (! Schema::hasColumn('pincodes', 'serviceable')) {
                $table->boolean('serviceable')->default(true)->after('pincode');
            }
            if (! Schema::hasColumn('pincodes', 'landing_page')) {
                $table->string('landing_page')->nullable()->after('serviceable');
            }
            if (! Schema::hasColumn('pincodes', 'priority')) {
                $table->enum('priority', ['high', 'medium', 'low'])->default('medium')->after('landing_page');
            }
        });

        if (Schema::hasColumn('pincodes', 'code') && Schema::hasColumn('pincodes', 'pincode')) {
            DB::table('pincodes')
                ->whereNull('pincode')
                ->whereNotNull('code')
                ->update(['pincode' => DB::raw('code')]);
        }

        Schema::table('intercepts', function (Blueprint $table): void {
            if (! Schema::hasColumn('intercepts', 'business_profile_id')) {
                $table->foreignId('business_profile_id')->nullable()->after('id')->constrained('business_profiles')->nullOnDelete();
            }
            if (! Schema::hasColumn('intercepts', 'keyword')) {
                $table->string('keyword')->nullable()->after('business_profile_id');
            }
            if (! Schema::hasColumn('intercepts', 'gap_type')) {
                $table->string('gap_type', 120)->nullable()->after('competitor_id');
            }
            if (! Schema::hasColumn('intercepts', 'action')) {
                $table->text('action')->nullable()->after('gap_type');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('page_elements')) {
            Schema::dropIfExists('page_elements');
        }

        if (Schema::hasTable('page_seo')) {
            Schema::dropIfExists('page_seo');
        }

        if (Schema::hasTable('business_profiles')) {
            Schema::dropIfExists('business_profiles');
        }
    }
};
