<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Public\PageRenderContextRegistrar;
use App\Support\InternalTemplatePageRedirects;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function __construct(
        private readonly PageRenderContextRegistrar $pageRenderContext,
    ) {}

    public function show(string $slug): View|RedirectResponse
    {
        $redirect = InternalTemplatePageRedirects::redirectPathFor($slug);
        if ($redirect !== null) {
            return redirect($redirect, 302);
        }

        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->pageRenderContext->register($page);

        return view('layouts.app', ['page' => $page]);
    }
}
