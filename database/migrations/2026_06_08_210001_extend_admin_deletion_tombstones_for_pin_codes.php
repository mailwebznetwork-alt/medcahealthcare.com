<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_deletion_tombstones', function (Blueprint $table): void {
            $table->string('source', 64)->nullable()->after('deleted_by_id');
            $table->string('reason', 500)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('admin_deletion_tombstones', function (Blueprint $table): void {
            $table->dropColumn(['source', 'reason']);
        });
    }
};
