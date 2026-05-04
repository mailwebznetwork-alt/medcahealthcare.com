<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blocks', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        foreach (DB::table('blocks')->orderBy('id')->get(['id']) as $row) {
            DB::table('blocks')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
        }

        Schema::table('blocks', function (Blueprint $table) {
            $table->text('description')->nullable()->after('blade_html');
            $table->string('block_type')->nullable()->after('description');
            $table->json('schema_json')->nullable()->after('block_type');
            $table->boolean('is_active')->default(true)->after('schema_json');
        });

        Schema::table('blocks', function (Blueprint $table) {
            $table->renameColumn('name', 'block_name');
            $table->renameColumn('slug', 'block_slug');
            $table->renameColumn('blade_html', 'code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blocks', function (Blueprint $table) {
            $table->renameColumn('block_name', 'name');
            $table->renameColumn('block_slug', 'slug');
            $table->renameColumn('code', 'blade_html');
        });

        Schema::table('blocks', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'description',
                'block_type',
                'schema_json',
                'is_active',
            ]);
        });
    }
};
