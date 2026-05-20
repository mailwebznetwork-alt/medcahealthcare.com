<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('focus_keywords')->nullable()->after('keywords');
            $table->json('heading_h2')->nullable()->after('h6');
            $table->json('heading_h3')->nullable()->after('heading_h2');
            $table->text('ai_context')->nullable()->after('aeo_answer');
            $table->string('search_intent')->nullable()->after('ai_context');
            $table->string('schema_type', 120)->nullable()->after('schema_json');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'focus_keywords',
                'heading_h2',
                'heading_h3',
                'ai_context',
                'search_intent',
                'schema_type',
            ]);
        });
    }
};
