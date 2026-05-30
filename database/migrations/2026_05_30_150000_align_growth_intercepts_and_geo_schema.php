<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('intercepts')) {
            Schema::table('intercepts', function (Blueprint $table): void {
                if (Schema::hasColumn('intercepts', 'title')) {
                    $table->string('title')->nullable()->change();
                }
                if (Schema::hasColumn('intercepts', 'channel')) {
                    $table->string('channel', 40)->nullable()->change();
                }
            });

            if (Schema::hasColumn('intercepts', 'priority')) {
                $driver = Schema::getConnection()->getDriverName();

                if ($driver === 'sqlite') {
                    // SQLite cannot alter column types in place; recreate when still integer-backed.
                    $columns = collect(Schema::getColumns('intercepts'));
                    $priorityType = (string) ($columns->firstWhere('name', 'priority')['type_name'] ?? '');

                    if (str_contains(strtolower($priorityType), 'int')) {
                        Schema::table('intercepts', function (Blueprint $table): void {
                            $table->dropIndex(['status', 'priority']);
                        });

                        Schema::rename('intercepts', 'intercepts_legacy');

                        Schema::create('intercepts', function (Blueprint $table): void {
                            $table->id();
                            $table->foreignId('business_profile_id')->nullable()->constrained('business_profiles')->nullOnDelete();
                            $table->string('keyword')->nullable();
                            $table->foreignId('competitor_id')->nullable()->constrained('competitors')->nullOnDelete();
                            $table->string('gap_type', 120)->nullable();
                            $table->text('action')->nullable();
                            $table->string('title')->nullable();
                            $table->string('channel', 40)->nullable();
                            $table->string('priority', 20)->default('medium');
                            $table->string('status', 40)->default('pending');
                            $table->text('notes')->nullable();
                            $table->timestamps();

                            $table->index(['status', 'priority']);
                        });

                        DB::table('intercepts_legacy')->orderBy('id')->chunkById(200, function ($rows): void {
                            foreach ($rows as $row) {
                                DB::table('intercepts')->insert([
                                    'id' => $row->id,
                                    'business_profile_id' => $row->business_profile_id ?? null,
                                    'keyword' => $row->keyword ?? $row->title ?? null,
                                    'competitor_id' => $row->competitor_id ?? null,
                                    'gap_type' => $row->gap_type ?? null,
                                    'action' => $row->action ?? null,
                                    'title' => $row->title ?? null,
                                    'channel' => $row->channel ?? null,
                                    'priority' => $this->mapLegacyPriority($row->priority ?? null),
                                    'status' => $this->mapLegacyStatus($row->status ?? null),
                                    'notes' => $row->notes ?? null,
                                    'created_at' => $row->created_at,
                                    'updated_at' => $row->updated_at,
                                ]);
                            }
                        });

                        Schema::drop('intercepts_legacy');
                    }
                } else {
                    DB::statement("UPDATE intercepts SET priority = CASE
                        WHEN priority IN ('high','medium','low') THEN priority
                        WHEN CAST(priority AS UNSIGNED) <= 1 THEN 'high'
                        WHEN CAST(priority AS UNSIGNED) >= 3 THEN 'low'
                        ELSE 'medium'
                    END");

                    Schema::table('intercepts', function (Blueprint $table): void {
                        $table->string('priority', 20)->default('medium')->change();
                        $table->string('status', 40)->default('pending')->change();
                    });
                }
            }
        }

        if (Schema::hasTable('geo_locations') && Schema::hasColumn('geo_locations', 'label')) {
            Schema::table('geo_locations', function (Blueprint $table): void {
                $table->string('label')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Non-destructive alignment migration; no rollback required for production safety.
    }

    private function mapLegacyPriority(mixed $priority): string
    {
        if (is_string($priority) && in_array($priority, ['high', 'medium', 'low'], true)) {
            return $priority;
        }

        $numeric = is_numeric($priority) ? (int) $priority : 2;

        return match (true) {
            $numeric <= 1 => 'high',
            $numeric >= 3 => 'low',
            default => 'medium',
        };
    }

    private function mapLegacyStatus(mixed $status): string
    {
        if (is_string($status) && in_array($status, ['pending', 'in_progress', 'completed', 'active'], true)) {
            return $status === 'active' ? 'pending' : $status;
        }

        return 'pending';
    }
};
