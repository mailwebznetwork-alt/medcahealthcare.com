<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->json('procedures')->nullable()->after('description');
            $table->json('specialized_care')->nullable()->after('procedures');
            $table->json('shifts')->nullable()->after('specialized_care');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['procedures', 'specialized_care', 'shifts']);
        });
    }
};
