<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'pincode')) {
                $table->string('pincode', 10)->nullable()->after('phone');
                $table->index('pincode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'pincode')) {
                $table->dropIndex(['pincode']);
                $table->dropColumn('pincode');
            }
        });
    }
};
