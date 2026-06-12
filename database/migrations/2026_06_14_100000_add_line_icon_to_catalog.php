<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['service_categories', 'services', 'sub_services'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasColumn($table, 'line_icon')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->string('line_icon', 64)->nullable()->after('icon_media_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'line_icon')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropColumn('line_icon');
                });
            }
        }
    }
};
