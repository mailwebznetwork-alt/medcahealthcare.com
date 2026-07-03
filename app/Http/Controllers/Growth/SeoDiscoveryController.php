<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Services\Growth\SeoDiscoveryBootstrapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SeoDiscoveryController extends Controller
{
    public function bootstrap(Request $request, SeoDiscoveryBootstrapService $bootstrap): RedirectResponse
    {
        $result = $bootstrap->run(pingSearchEngines: true);

        return redirect()
            ->route('growth-center.competitors.index', ['tab' => 'seo'])
            ->with('status', __('SEO discovery bootstrap completed.'))
            ->with('seo_bootstrap_result', $result);
    }
}
