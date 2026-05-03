<?php

use App\Models\User;
use App\ModuleAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('module_access')->nullable()->after('password');
        });

        $defaults = ModuleAccess::defaultGrants();

        User::query()->update(['module_access' => $defaults]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('module_access');
        });
    }
};
