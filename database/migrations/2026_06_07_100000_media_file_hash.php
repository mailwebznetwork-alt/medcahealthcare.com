<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->string('file_hash', 64)->nullable()->after('file_size');
            $table->string('legacy_path')->nullable()->after('file_hash');

            $table->index('file_hash');
            $table->index('legacy_path');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropIndex(['file_hash']);
            $table->dropIndex(['legacy_path']);
            $table->dropColumn(['file_hash', 'legacy_path']);
        });
    }
};
