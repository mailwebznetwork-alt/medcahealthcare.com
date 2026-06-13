<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CATALOG_TABLES = ['service_categories', 'services', 'sub_services'];

    public function up(): void
    {
        if (! Schema::hasTable('bangalore_zones')) {
            Schema::create('bangalore_zones', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 120);
                $table->string('slug', 120)->unique();
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        foreach (self::CATALOG_TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'quick_answer')) {
                    $table->text('quick_answer')->nullable()->after('ai_summary');
                }
                if (! Schema::hasColumn($tableName, 'why_medca')) {
                    $table->text('why_medca')->nullable()->after('quick_answer');
                }
                if (! Schema::hasColumn($tableName, 'key_takeaways')) {
                    $table->json('key_takeaways')->nullable()->after('why_medca');
                }
                if (! Schema::hasColumn($tableName, 'activities_included')) {
                    $table->json('activities_included')->nullable()->after('key_takeaways');
                }
                if (! Schema::hasColumn($tableName, 'medical_review_status')) {
                    $table->string('medical_review_status', 32)->default('draft')->after('activities_included');
                }
                if (! Schema::hasColumn($tableName, 'reviewed_by')) {
                    $table->foreignId('reviewed_by')->nullable()->after('medical_review_status')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn($tableName, 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
                if (! Schema::hasColumn($tableName, 'verification_status')) {
                    $table->string('verification_status', 32)->default('unverified')->after('reviewed_at');
                }
                if (! Schema::hasColumn($tableName, 'featured_video_url')) {
                    $table->string('featured_video_url', 500)->nullable()->after('verification_status');
                }
                if (! Schema::hasColumn($tableName, 'featured_video_title')) {
                    $table->string('featured_video_title', 255)->nullable()->after('featured_video_url');
                }
                if (! Schema::hasColumn($tableName, 'featured_video_description')) {
                    $table->text('featured_video_description')->nullable()->after('featured_video_title');
                }
            });
        }

        if (Schema::hasTable('pin_codes') && ! Schema::hasColumn('pin_codes', 'bangalore_zone_id')) {
            Schema::table('pin_codes', function (Blueprint $table): void {
                $table->foreignId('bangalore_zone_id')->nullable()->after('geo_location_id')->constrained('bangalore_zones')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('import_approval_requests')) {
            Schema::create('import_approval_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 32)->default('pending');
                $table->string('entity_key', 64)->nullable();
                $table->string('workbook', 64)->nullable();
                $table->string('staging_path', 500);
                $table->string('original_filename', 255)->nullable();
                $table->unsignedInteger('total_data_rows')->default(0);
                $table->string('staging_checksum', 64)->nullable();
                $table->json('staging_meta')->nullable();
                $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('requested_at');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('import_approval_requests');

        if (Schema::hasTable('pin_codes') && Schema::hasColumn('pin_codes', 'bangalore_zone_id')) {
            Schema::table('pin_codes', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('bangalore_zone_id');
            });
        }

        foreach (self::CATALOG_TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $columns = [
                'quick_answer', 'why_medca', 'key_takeaways', 'activities_included',
                'medical_review_status', 'reviewed_by', 'reviewed_at', 'verification_status',
                'featured_video_url', 'featured_video_title', 'featured_video_description',
            ];

            $existing = array_filter($columns, fn (string $col): bool => Schema::hasColumn($tableName, $col));

            if ($existing !== []) {
                Schema::table($tableName, function (Blueprint $table) use ($existing, $tableName): void {
                    if (in_array('reviewed_by', $existing, true)) {
                        $table->dropConstrainedForeignId('reviewed_by');
                        $existing = array_diff($existing, ['reviewed_by']);
                    }
                    if ($existing !== []) {
                        $table->dropColumn(array_values($existing));
                    }
                });
            }
        }

        Schema::dropIfExists('bangalore_zones');
    }
};
