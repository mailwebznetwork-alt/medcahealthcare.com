<?php

namespace App\Http\Controllers\Public;

use App\Enums\PageCategory;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceLocationPage;
use App\Services\Operations\ServiceDetailPageProvisioner;
use App\Services\Operations\ServiceLocationPageProvisioner;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HtmlSitemapController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) config('services_master.html_sitemap.per_page', 50);

        $services = Service::query()->publicListing()->orderBy('title')->get();
        $locations = ServiceLocationPage::query()
            ->with(['service', 'pincode', 'page'])
            ->whereHas('service', fn ($s) => $s->where('is_active', true))
            ->orderBy('id')
            ->get()
            ->filter(fn (ServiceLocationPage $row): bool => $row->service?->isListedPublicly() ?? false);

        $webPages = Page::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('page_category', PageCategory::Web)
                    ->orWhereIn('slug', config('services_master.web_page_slugs', []));
            })
            ->orderBy('title')
            ->get();

        $blogs = Blog::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->limit(100)
            ->get();

        $landingPages = Page::query()
            ->where('is_active', true)
            ->where('page_category', PageCategory::Landing)
            ->orderBy('title')
            ->get();

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $services = $services->filter(fn (Service $s): bool => str_contains(mb_strtolower($s->title.' '.$s->service_code), $needle));
            $locations = $locations->filter(function (ServiceLocationPage $row) use ($needle): bool {
                $title = $row->page?->title ?? app(ServiceLocationPageProvisioner::class)->locationTitle($row->service, $row->pincode);

                return str_contains(mb_strtolower($title), $needle);
            });
            $webPages = $webPages->filter(fn (Page $p): bool => str_contains(mb_strtolower($p->title.' '.$p->slug), $needle));
        }

        return view('public.sitemap.html', [
            'search' => $q,
            'services' => $services,
            'locations' => $locations,
            'webPages' => $webPages,
            'blogs' => $blogs,
            'landingPages' => $landingPages,
            'perPage' => $perPage,
        ]);
    }
}
