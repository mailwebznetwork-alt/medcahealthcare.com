<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Public\PageRenderContextRegistrar;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function __construct(
        private readonly PageRenderContextRegistrar $pageRenderContext,
    ) {}

    public function show(string $slug): View
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->pageRenderContext->register($page);

        return view('layouts.app', ['page' => $page]);
    }
}
