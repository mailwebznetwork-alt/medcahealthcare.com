<?php

namespace App\Http\Controllers\Growth;

use App\Http\Controllers\Controller;
use App\Jobs\Growth\ScanBrokenInternalLinksJob;
use App\Models\BrokenLinkReport;
use App\Models\UrlRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UrlRedirectController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_path' => ['required', 'string', 'max:2048'],
            'to_url' => ['required', 'string', 'max:2048'],
            'status_code' => ['required', Rule::in([301, 302, 307, 308])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        UrlRedirect::query()->updateOrCreate(
            ['from_path' => UrlRedirect::normalizePath($validated['from_path'])],
            [
                'to_url' => $validated['to_url'],
                'status_code' => (int) $validated['status_code'],
                'is_active' => true,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return redirect()
            ->route('growth-center.competitors.index', ['tab' => 'seo'])
            ->with('status', __('Redirect saved.'));
    }

    public function destroy(UrlRedirect $urlRedirect): RedirectResponse
    {
        $urlRedirect->delete();

        return redirect()
            ->route('growth-center.competitors.index', ['tab' => 'seo'])
            ->with('status', __('Redirect removed.'));
    }

    public function scanBrokenLinks(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('broken_link_reports')) {
            return redirect()
                ->route('growth-center.competitors.index', ['tab' => 'seo'])
                ->with('status', __('Broken link reports table is not migrated.'));
        }

        ScanBrokenInternalLinksJob::dispatch();

        return redirect()
            ->route('growth-center.competitors.index', ['tab' => 'seo'])
            ->with('status', __('Broken link scan queued.'));
    }
}
