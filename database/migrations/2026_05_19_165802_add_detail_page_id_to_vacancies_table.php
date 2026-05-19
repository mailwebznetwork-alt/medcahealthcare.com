<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->foreignId('detail_page_id')
                ->nullable()
                ->after('sort_order')
                ->constrained('pages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('detail_page_id');
        });
    }
};
