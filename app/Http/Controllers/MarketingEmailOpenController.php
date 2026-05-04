<?php

namespace App\Http\Controllers;

use App\Models\MarketingEmailTracker;
use Illuminate\Http\Response;

class MarketingEmailOpenController extends Controller
{
    public function pixel(string $token): Response
    {
        $tracker = MarketingEmailTracker::query()->where('token', $token)->first();
        if ($tracker !== null) {
            $tracker->increment('open_count');
        }

        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
