<?php

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
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('profile_image_path', 2048)->nullable()->after('phone');
            $table->string('role_label', 120)->nullable()->after('profile_image_path');
            $table->boolean('is_active')->default(true)->after('role_label');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'profile_image_path',
                'role_label',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
