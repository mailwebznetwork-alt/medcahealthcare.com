<?php

use App\Models\UrlRedirect;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('url_redirects')) {
            return;
        }

        UrlRedirect::query()->updateOrCreate(
            ['from_path' => UrlRedirect::normalizePath('/home-nursing/bangalore')],
            [
                'to_url' => 'https://medcahealthcare.com/services',
                'status_code' => 301,
                'is_active' => true,
                'notes' => 'Google Ads landing: www.medcahealthcare.com/home-nursing/bangalore → /services (preserves gclid/UTMs).',
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('url_redirects')) {
            return;
        }

        UrlRedirect::query()
            ->where('from_path', UrlRedirect::normalizePath('/home-nursing/bangalore'))
            ->delete();
    }
};
