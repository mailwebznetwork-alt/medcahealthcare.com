<?php

namespace App\Http\Middleware;

use App\Models\UrlRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ApplyUrlRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('url_redirects') || ! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = UrlRedirect::normalizePath($request->getPathInfo());

        $redirect = UrlRedirect::query()
            ->where('is_active', true)
            ->where('from_path', $path)
            ->first();

        if ($redirect === null) {
            return $next($request);
        }

        $redirect->increment('hit_count');

        $status = in_array((int) $redirect->status_code, [301, 302, 307, 308], true)
            ? (int) $redirect->status_code
            : 301;

        $target = $redirect->to_url;
        if (! str_starts_with($target, 'http://') && ! str_starts_with($target, 'https://')) {
            $target = url('/'.ltrim($target, '/'));
        }

        return redirect()->away($target, $status);
    }
}
