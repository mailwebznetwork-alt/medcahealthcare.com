<?php

use App\Http\Controllers\Careers\CareersController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Growth\AeoController;
use App\Http\Controllers\Growth\CompetitorPageController;
use App\Http\Controllers\Growth\GeoController;
use App\Http\Controllers\Growth\SeoController;
use App\Http\Controllers\Growth\WarRoomController;
use App\Http\Controllers\Marketing\MarketingReportController;
use App\Http\Controllers\MarketingEmailOpenController;
use App\Http\Controllers\MarketingTrackingController;
use App\Http\Controllers\ModuleSurfaceController;
use App\Http\Controllers\Operations\JobPortal\ApplicationController;
use App\Http\Controllers\Operations\JobPortal\JobPortalDashboardController;
use App\Http\Controllers\Operations\JobPortal\VacancyController;
use App\Http\Controllers\Operations\LegacyModuleFieldsController;
use App\Http\Controllers\Operations\OperationsHubController;
use App\Http\Controllers\Operations\BulkImportController;
use App\Http\Controllers\Operations\PinCodes\PinCodeController;
use App\Http\Controllers\Operations\ServiceCategories\ServiceCategoryController;
use App\Http\Controllers\Operations\Services\ServiceController;
use App\Http\Controllers\Operations\Services\SubServiceController;
use App\Http\Controllers\Public\ServiceCategoryPublicController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\CmsPageController;
use App\Http\Controllers\Public\LocationAreaController;
use App\Http\Controllers\Public\LeadCaptureController;
use App\Http\Controllers\Public\LocationController;
use App\Http\Controllers\Public\ServicePublicController;
use App\Http\Controllers\Settings\IntegrationController;
use App\Http\Controllers\Settings\SystemOperationsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SiteArchitect\DynamicRecordController;
use App\Http\Controllers\SiteArchitect\ModuleManagerController;
use App\Http\Controllers\System\SourceOfTruthController;
use App\Http\Controllers\System\SystemOverviewController;
use App\Http\Controllers\ThemePreviewController;
use App\Http\Controllers\UserManagement\UserController;
use App\Http\Controllers\WorkspaceSearchController;
use App\Models\Blog;
use App\Models\Lead;
use App\Models\Page;
use App\Models\SiteSlugRedirect;
use App\Services\ActivityLogService;
use App\Services\Public\PagePublicPreviewService;
use App\Services\Public\PageRenderContextRegistrar;
use App\Services\Public\PublicPagePresenter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SeoController::class, 'robotsTxt'])->name('public.robots');
Route::get('/sitemap', [\App\Http\Controllers\Public\HtmlSitemapController::class, 'index'])->name('public.sitemap.html');
Route::get('/sitemap.xml', [SeoController::class, 'sitemapXml'])->name('public.sitemap');
Route::get('/sitemap-{segment}.xml', [SeoController::class, 'sitemapSegmentXml'])
    ->where('segment', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.sitemap.segment');
Route::get('/llm.txt', [AeoController::class, 'llmTxt'])->name('public.llm');
Route::get('/ai-discovery', [AeoController::class, 'discovery'])->name('public.ai-discovery');

Route::post('/leads', [LeadCaptureController::class, 'store'])
    ->middleware('throttle:public_leads')
    ->name('public.leads.store');

Route::get('/location/pincode/{pincode}', [LocationController::class, 'selectPincode'])
    ->where('pincode', '\d{6}')
    ->middleware('throttle:60,1')
    ->name('location.pincode.select');
Route::post('/location/pincode', [LocationController::class, 'storePincode'])
    ->middleware('throttle:20,1')
    ->name('location.pincode.store');
Route::post('/location/geolocation', [LocationController::class, 'storeGeolocation'])
    ->middleware('throttle:20,1')
    ->name('location.geolocation.store');

Route::get('/', function () {
    $page = Page::query()->where('slug', 'home')->where('is_active', true)->first();

    if ($page !== null) {
        app(PageRenderContextRegistrar::class)->register($page);

        return view('layouts.app', ['page' => $page]);
    }

    return view('home', app(PublicPagePresenter::class)->nearYouPayload());
})->name('public.home');

Route::get('/services-catalog', [ServicePublicController::class, 'index'])->name('public.services.index');

Route::get('/service-categories', [ServiceCategoryPublicController::class, 'index'])->name('public.service-categories.index');
Route::get('/service-categories/{code}', [ServiceCategoryPublicController::class, 'show'])
    ->where('code', '[a-z][a-z0-9-]*')
    ->name('public.service-categories.show');

Route::get('/t/mail/{token}/open.gif', [MarketingEmailOpenController::class, 'pixel'])
    ->middleware('throttle:120,1')
    ->name('marketing.email-open-pixel');

Route::get('/locations/{slug}', [LocationAreaController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.locations.area');

foreach (config('public_pages.root_slugs', []) as $cmsSlug) {
    $routeName = match ($cmsSlug) {
        'careers' => 'careers.index',
        'services' => 'public.page.services',
        default => 'public.page.'.str_replace('-', '_', $cmsSlug),
    };

    Route::get('/'.$cmsSlug, [CmsPageController::class, 'show'])
        ->defaults('slug', $cmsSlug)
        ->name($routeName);
}

Route::get('/services/{code}/{city}/{pincode}', [ServicePublicController::class, 'showLocationPincode'])
    ->where('code', '[A-Za-z][A-Za-z0-9_-]*')
    ->where('city', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->where('pincode', '\d{6}')
    ->name('public.services.location.pincode');

Route::get('/services/{code}/sub/{subCode}', [ServicePublicController::class, 'showSubService'])
    ->where('code', '[A-Za-z][A-Za-z0-9_-]*')
    ->where('subCode', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.services.sub');

Route::get('/services/{code}/{locationSlug}', [ServicePublicController::class, 'showLocation'])
    ->where('code', '[A-Za-z][A-Za-z0-9_-]*')
    ->where('locationSlug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.services.location');

Route::get('/services/{code}', [ServicePublicController::class, 'show'])
    ->where('code', '[A-Za-z][A-Za-z0-9_-]*')
    ->name('public.services.show');

Route::get('/careers/{slug}', [CareersController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('careers.show');
Route::post('/careers/{slug}/apply', [CareersController::class, 'storeApplication'])
    ->middleware('throttle:10,1')
    ->name('careers.apply');

Route::get('/p/{slug}', function (string $slug) {
    $legacyTarget = app(\App\Services\Operations\ServicePublicUrlBuilder::class)->legacyRedirectForPageSlug($slug);
    if ($legacyTarget !== null) {
        return redirect($legacyTarget, 301);
    }

    $templateRedirect = \App\Support\InternalTemplatePageRedirects::redirectPathFor($slug);
    if ($templateRedirect !== null) {
        return redirect($templateRedirect, 302);
    }

    $page = Page::query()->where('slug', $slug)->first();

    if ($page !== null && $page->is_active) {
        if (Page::usesRootPublicPath($slug)) {
            return redirect($page->publicPath(), 301);
        }

        app(PageRenderContextRegistrar::class)->register($page);

        return view('layouts.app', ['page' => $page]);
    }

    $target = $slug;
    $guard = 0;
    while ($guard++ < 12) {
        $row = SiteSlugRedirect::query()->where('from_slug', $target)->first();
        if ($row === null) {
            break;
        }
        $target = $row->to_slug;
    }

    if ($target !== $slug) {
        if (str_starts_with($target, 'services/')) {
            return redirect('/'.$target, 301);
        }

        if (Page::usesRootPublicPath($target)) {
            return redirect(Page::publicPathForSlug($target), 301);
        }

        return redirect('/p/'.$target, 301);
    }

    abort(404);
})->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('pages.public');

Route::get('/blog/{blog:slug}', function (Blog $blog) {
    abort_unless($blog->is_published, 404);
    if ($blog->published_at?->isFuture()) {
        abort(404);
    }

    return view('layouts.app', ['blog' => $blog]);
})->name('blog.public');

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:dashboard', 'role:viewer,editor,manager,admin,super_admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:site_architect', 'role:editor,manager,admin,super_admin'])->group(function () {
    Route::get('/site-architect', function () {
        return redirect()->route('site-architect.pages.index');
    })->name('modules.site-architect');

    Route::prefix('site-architect')->name('site-architect.')->group(function () {
        Route::view('/pages', 'site-architect.pages-shell')->name('pages.index');
        Route::get('/pages/{page}/preview', function (Page $page) {
            Gate::authorize('view', $page);

            app(ActivityLogService::class)->log(
                'page_preview',
                'site_architect',
                'Page ID '.$page->id.' slug '.$page->slug
            );

            return view(
                'layouts.app',
                app(PagePublicPreviewService::class)->viewDataFor($page)
            );
        })->name('pages.preview');

        Route::view('/navigation', 'site-architect.navigation-shell')->name('navigation.index');

        Route::view('/blogs', 'site-architect.blogs-shell')->name('blogs.index');
        Route::get('/blogs/{blog}/preview', function (Blog $blog) {
            Gate::authorize('view', $blog);

            return view('layouts.app', ['blog' => $blog]);
        })->name('blogs.preview');

        Route::view('/block-factory', 'site-architect.block-factory-shell')->name('block-factory.index');

        Route::view('/blueprint-builder', 'site-architect.blueprint-builder-shell')->name('blueprint-builder.index');

        Route::view('/section-library', 'site-architect.section-library-shell')->name('section-library.index');
        Route::redirect('/sections', '/site-architect/section-library', 301)->name('sections.index');

        Route::view('/block-presets', 'site-architect.block-presets-shell')->name('block-presets.index');
        Route::redirect('/presets', '/site-architect/block-presets', 301)->name('presets.index');

        Route::view('/block-studio', 'site-architect.block-studio-shell')->name('block-studio.index');

        Route::view('/deployment-packages', 'site-architect.deployment-packages-shell')->name('deployment-packages.index');

        Route::view('/media', 'site-architect.media-library-shell')->name('media.index');

        Route::get('/bulk/export', \App\Http\Controllers\SiteArchitect\BulkExportController::class)->name('bulk.export');

        Route::prefix('modules')->name('modules.')->group(function () {
            Route::get('/', [ModuleManagerController::class, 'index'])->name('index');
            Route::get('/create', [ModuleManagerController::class, 'create'])->name('create');
            Route::post('/', [ModuleManagerController::class, 'store'])->name('store');
            Route::get('/{module}/edit', [ModuleManagerController::class, 'edit'])->name('edit');
            Route::put('/{module}', [ModuleManagerController::class, 'update'])->name('update');
            Route::delete('/{module}', [ModuleManagerController::class, 'destroy'])->name('destroy');

            Route::prefix('{module}/records')->name('records.')->group(function () {
                Route::get('/', [DynamicRecordController::class, 'index'])->name('index');
                Route::get('/create', [DynamicRecordController::class, 'create'])->name('create');
                Route::post('/', [DynamicRecordController::class, 'store'])->name('store');
                Route::get('/{record}/edit', [DynamicRecordController::class, 'edit'])->name('edit');
                Route::put('/{record}', [DynamicRecordController::class, 'update'])->name('update');
                Route::delete('/{record}', [DynamicRecordController::class, 'destroy'])->name('destroy');
            });
        });
    });
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:operations', 'role:manager,admin,super_admin'])->group(function () {
    Route::get('/operations', OperationsHubController::class)->name('modules.operations');

    Route::put('operations/managed-modules/{module}/fields', [LegacyModuleFieldsController::class, 'update'])
        ->name('operations.managed-modules.fields.update');

    Route::prefix('operations/job-portal')->name('operations.job-portal.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('operations.job-portal.overview');
        })->name('index');
        Route::get('overview', JobPortalDashboardController::class)->name('overview');
        Route::post('vacancies/{vacancy}/duplicate', [VacancyController::class, 'duplicate'])->name('vacancies.duplicate');
        Route::resource('vacancies', VacancyController::class);
        Route::get('applications/{application}/resume', [ApplicationController::class, 'downloadResume'])
            ->name('applications.resume');
        Route::resource('applications', ApplicationController::class)->only(['index', 'show', 'update']);
    });

    Route::prefix('operations/service-categories')->name('operations.service-categories.')->group(function () {
        Route::get('/', [ServiceCategoryController::class, 'index'])->name('index');
        Route::get('create', [ServiceCategoryController::class, 'create'])->name('create');
        Route::post('/', [ServiceCategoryController::class, 'store'])->name('store');
        Route::get('{service_category}/edit', [ServiceCategoryController::class, 'edit'])->name('edit');
        Route::put('{service_category}', [ServiceCategoryController::class, 'update'])->name('update');
        Route::delete('{service_category}', [ServiceCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('operations/services')->name('operations.services.')->group(function () {
        Route::get('bulk-import', [BulkImportController::class, 'servicesWorkbook'])->name('bulk-import');
        Route::post('bulk-import/preview', [BulkImportController::class, 'preview'])->name('bulk-import.preview');
        Route::post('bulk-import/confirm', [BulkImportController::class, 'confirm'])->name('bulk-import.confirm');
        Route::post('bulk-import/cancel', [BulkImportController::class, 'cancel'])->name('bulk-import.cancel');
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::get('create', [ServiceController::class, 'create'])->name('create');
        Route::post('/', [ServiceController::class, 'store'])->name('store');
        Route::get('{service}/duplicate', [ServiceController::class, 'duplicate'])->name('duplicate');
        Route::get('{service}/preview', [ServiceController::class, 'preview'])->name('preview');
        Route::get('{service}/sub-services', [SubServiceController::class, 'index'])->name('sub-services.index');
        Route::get('{service}/sub-services/create', [SubServiceController::class, 'create'])->name('sub-services.create');
        Route::post('{service}/sub-services', [SubServiceController::class, 'store'])->name('sub-services.store');
        Route::get('{service}/sub-services/{sub_service}/edit', [SubServiceController::class, 'edit'])->name('sub-services.edit');
        Route::put('{service}/sub-services/{sub_service}', [SubServiceController::class, 'update'])->name('sub-services.update');
        Route::delete('{service}/sub-services/{sub_service}', [SubServiceController::class, 'destroy'])->name('sub-services.destroy');
        Route::get('{service}/edit', [ServiceController::class, 'edit'])->name('edit');
        Route::get('{service}/detail-page/create', [ServiceController::class, 'createDetailPage'])->name('detail-page.create');
        Route::post('{service}/detail-page', [ServiceController::class, 'storeDetailPage'])->name('detail-page.store');
        Route::get('{service}/detail-page/edit', [ServiceController::class, 'editDetailPage'])->name('detail-page.edit');
        Route::put('{service}', [ServiceController::class, 'update'])->name('update');
        Route::post('{service}/gemini-suggest', [ServiceController::class, 'geminiSuggest'])->name('gemini-suggest');
        Route::delete('{service}', [ServiceController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('operations/bulk-import')->name('operations.bulk-import.')->group(function () {
        Route::get('/', [BulkImportController::class, 'index'])->name('index');
        Route::get('templates/{workbook}', [BulkImportController::class, 'downloadTemplate'])->name('templates.download');
        Route::post('preview', [BulkImportController::class, 'preview'])->name('preview');
        Route::post('confirm', [BulkImportController::class, 'confirm'])->name('confirm');
        Route::post('cancel', [BulkImportController::class, 'cancel'])->name('cancel');
        Route::post('rollback/{batch}', [BulkImportController::class, 'rollback'])->name('rollback');
    });

    Route::prefix('operations/pin-codes')->name('operations.pin-codes.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('operations.pin-codes.overview');
        })->name('index');
        Route::get('overview', [PinCodeController::class, 'overview'])->name('overview');
        Route::get('directory', [PinCodeController::class, 'directory'])->name('directory');
        Route::get('bulk-import', [BulkImportController::class, 'pincodesWorkbook'])->name('bulk-import');
        Route::post('bulk-import/preview', [BulkImportController::class, 'preview'])->name('bulk-import.preview');
        Route::post('bulk-import/confirm', [BulkImportController::class, 'confirm'])->name('bulk-import.confirm');
        Route::post('bulk-import/cancel', [BulkImportController::class, 'cancel'])->name('bulk-import.cancel');
        Route::get('create', [PinCodeController::class, 'create'])->name('create');
        Route::post('/', [PinCodeController::class, 'store'])->name('store');
        Route::get('{pin_code}/edit', [PinCodeController::class, 'edit'])->name('edit');
        Route::put('{pin_code}', [PinCodeController::class, 'update'])->name('update');
        Route::delete('{pin_code}', [PinCodeController::class, 'destroy'])->name('destroy');
        Route::patch('{pin_code}/activate', [PinCodeController::class, 'activate'])->name('activate');
        Route::patch('{pin_code}/deactivate', [PinCodeController::class, 'deactivate'])->name('deactivate');
    });

    Route::prefix('operations/bookings')->name('operations.bookings.')->group(function () {
        Route::view('/', 'operations.bookings.index-shell')->name('index');
        Route::get('{lead}', function (Lead $lead) {
            return view('operations.bookings.show-shell', ['lead' => $lead]);
        })->name('show');
    });

    Route::view('/operations/admissions', 'operations.admissions.index-shell')->name('operations.admissions.index');
    Route::view('/operations/revenue-events', 'operations.revenue-events.index-shell')->name('operations.revenue-events.index');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:marketing', 'role:manager,admin,super_admin'])->group(function () {
    Route::view('/marketing/dashboard', 'marketing.dashboard-shell')->name('marketing.dashboard');
    Route::redirect('/marketing', '/marketing/dashboard', 301)->name('modules.marketing');

    Route::view('/marketing/intelligence', 'marketing.intelligence-shell')->name('marketing.intelligence');

    Route::get('/marketing/campaigns', static fn () => redirect()->to(route('marketing.dashboard').'#marketing-campaigns', 302))
        ->name('marketing.campaigns');

    Route::get('/marketing/attribution', static fn () => redirect()->route('marketing.intelligence', ['tab' => 'attribution'], 302))
        ->name('marketing.attribution');

    Route::get('/marketing/reports', static fn () => redirect()->route('modules.marketing.reports.leads.export', [], 302))
        ->name('marketing.reports');

    Route::get('/marketing/reports/leads/export', [MarketingReportController::class, 'exportLeads'])
        ->name('modules.marketing.reports.leads.export');
});

Route::post('/marketing/track', [MarketingTrackingController::class, 'store'])
    ->middleware('throttle:marketing_clicks')
    ->name('marketing.track');

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:growth_center', 'role:viewer,editor,manager,admin,super_admin'])->group(function () {
    Route::get('/growth-center', function () {
        return redirect()->route('growth-center.competitors.index');
    })->name('modules.growth-center');
    Route::get('/growth-center/competitors', CompetitorPageController::class)->name('growth-center.competitors.index');
    Route::get('/growth-center/readiness', [CompetitorPageController::class, 'hubTab'])->defaults('tab', 'readiness')->name('growth-center.readiness');
    Route::get('/growth-center/ga4', [CompetitorPageController::class, 'hubTab'])->defaults('tab', 'ga4')->name('growth-center.ga4.index');
    Route::get('/growth-center/ai-pulse', [CompetitorPageController::class, 'hubTab'])->defaults('tab', 'ai-pulse')->name('growth-center.ai-pulse.index');
    Route::get('/growth-center/aeo', [CompetitorPageController::class, 'hubTab'])->defaults('tab', 'seo')->name('growth-center.aeo.index');

    Route::redirect('/growth-center/competitors/readiness', '/growth-center/readiness', 301);
    Route::redirect('/growth-center/competitors/ga4', '/growth-center/ga4', 301);
    Route::redirect('/growth-center/competitors/ai-pulse', '/growth-center/ai-pulse', 301);

    Route::prefix('growth-center')->group(function () {
        Route::redirect('seo', '/growth-center/seo/entity', 301)->name('growth-center.seo.index');

        Route::prefix('seo')->group(function () {
            Route::get('entity', [SeoController::class, 'entity'])->name('growth-center.seo.entity');
            Route::get('technical', [SeoController::class, 'technical'])->name('growth-center.seo.technical');
        });

        Route::prefix('geo')->group(function () {
            Route::get('location', [GeoController::class, 'location'])->name('growth-center.geo.location');
            Route::get('pincodes', [GeoController::class, 'pincodes'])->name('growth-center.geo.pincodes');
        });

        Route::prefix('war-room')->group(function () {
            Route::get('/', [WarRoomController::class, 'dashboard'])->name('growth-center.war-room');
            Route::redirect('dashboard', '/growth-center/war-room', 301)->name('growth-center.war-room.dashboard');
            Route::get('intercepts', [WarRoomController::class, 'intercepts'])->name('growth-center.war-room.intercepts');
        });
    });

    Route::redirect('/growth-center/war-room/dashboard', '/growth-center/war-room', 301);
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:growth_center', 'role:editor,manager,admin,super_admin'])->group(function () {
    Route::post('/growth-center/competitors', [CompetitorPageController::class, 'store'])->name('growth-center.competitors.store');
    Route::post('/growth-center/competitors/bulk', [CompetitorPageController::class, 'bulkStore'])->name('growth-center.competitors.bulk-store');
    Route::post('/growth-center/competitors/compare', [CompetitorPageController::class, 'compare'])->name('growth-center.competitors.compare');
    Route::post('/growth-center/competitors/keywords', [CompetitorPageController::class, 'storeKeyword'])->name('growth-center.competitors.keywords.store');
    Route::post('/growth-center/competitors/keywords/bulk', [CompetitorPageController::class, 'storeKeywordsBulk'])->name('growth-center.competitors.keywords.bulk-store');
    Route::post('/growth-center/competitors/tracking', [CompetitorPageController::class, 'storeTracking'])->name('growth-center.competitors.tracking.store');
    Route::post('/growth-center/competitors/our-ranking', [CompetitorPageController::class, 'storeOurRanking'])->name('growth-center.competitors.our-ranking.store');
    Route::post('/growth-center/competitors/leads', [CompetitorPageController::class, 'storeLead'])->name('growth-center.competitors.leads.store');
    Route::delete('/growth-center/competitors/{competitor}', [CompetitorPageController::class, 'destroy'])->name('growth-center.competitors.destroy');

    Route::prefix('growth-center')->group(function () {
        Route::prefix('seo')->group(function () {
            Route::post('entity', [SeoController::class, 'storeEntity'])->name('growth-center.seo.entity.store');
            Route::post('technical', [SeoController::class, 'storeTechnical'])->name('growth-center.seo.technical.store');
        });

        Route::post('aeo', [AeoController::class, 'store'])->name('growth-center.aeo.store');

        Route::prefix('geo')->group(function () {
            Route::post('location', [GeoController::class, 'storeLocation'])->name('growth-center.geo.location.store');
            Route::post('pincode', [GeoController::class, 'storePincode'])->name('growth-center.geo.pincode.store');
            Route::put('pincode/{id}', [GeoController::class, 'updatePincode'])->name('growth-center.geo.pincode.update');
        });

        Route::prefix('war-room')->group(function () {
            Route::post('intercept', [WarRoomController::class, 'storeIntercept'])->name('growth-center.war-room.intercept.store');
            Route::put('intercept/{id}', [WarRoomController::class, 'updateIntercept'])->name('growth-center.war-room.intercept.update');
        });
    });
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:user_management'])->prefix('user-management')->name('user-management.')->group(function () {
    Route::middleware(['role:viewer,editor,manager,admin,super_admin'])->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
    });

    Route::middleware(['role:manager,admin,super_admin'])->group(function () {
        Route::get('create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('{user}', [UserController::class, 'update'])->name('update');
        Route::patch('{user}/activate', [UserController::class, 'activate'])->name('activate');
        Route::patch('{user}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
    });

    Route::middleware(['role:super_admin'])->group(function () {
        Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:security', 'role:admin,super_admin'])->group(function () {
    Route::get('/security', [ModuleSurfaceController::class, 'show'])
        ->defaults('momModule', 'security')
        ->name('modules.security');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'role:admin,super_admin'])->prefix('admin/notifications')->name('admin.notifications.')->group(function () {
    Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
    Route::post('/read-all', [AdminNotificationController::class, 'markAllRead'])->name('read-all');
    Route::match(['get', 'patch'], '/{notification}/read', [AdminNotificationController::class, 'markRead'])->name('read');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:system', 'role:admin,super_admin'])->group(function () {
    Route::get('/system', fn () => redirect()->route('system.overview', [], 301))->name('system.index');
    Route::get('/system/overview', [SystemOverviewController::class, 'index'])->name('system.overview');
    Route::get('/system/source-of-truth', [SourceOfTruthController::class, 'index'])->name('system.source-of-truth');
    Route::get('/system/queue', [SystemOverviewController::class, 'queue'])->name('system.queue');
    Route::get('/system/scheduler', [SystemOverviewController::class, 'scheduler'])->name('system.scheduler');
    Route::get('/system/health', [SystemOverviewController::class, 'health'])->name('system.health');
    Route::redirect('/system/integrations', '/settings/integrations', 301)->name('system.integrations');
    Route::get('/settings/integrations', [SettingsController::class, 'integrations'])->name('settings.integrations');
    Route::get('/settings/webhooks', [SettingsController::class, 'webhooks'])->name('settings.webhooks');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:settings', 'role:admin,super_admin'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/appearance', [SettingsController::class, 'appearance'])->name('settings.appearance');
    Route::get('/settings/global-content', [SettingsController::class, 'globalContent'])->name('settings.global-content');
    Route::post('/settings/appearance/preview/enable', [ThemePreviewController::class, 'enable'])->name('settings.appearance.preview.enable');
    Route::post('/settings/appearance/preview/disable', [ThemePreviewController::class, 'disable'])->name('settings.appearance.preview.disable');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:settings', 'role:super_admin', 'backup.operator'])->group(function () {
    Route::get('/settings/backup', [SettingsController::class, 'backup'])->name('settings.backup');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:settings', 'role:super_admin'])->group(function () {
    Route::get('/settings/maintenance', [SettingsController::class, 'maintenance'])->name('settings.maintenance');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:settings', 'role:super_admin', 'backup.operator'])->prefix('settings/system')->name('settings.system.')->group(function () {
    Route::post('backup', [SystemOperationsController::class, 'backup'])->name('backup');
    Route::get('backup/download', [SystemOperationsController::class, 'downloadBackup'])->name('backup.download');
    Route::post('backup/restore', [SystemOperationsController::class, 'restoreBackup'])->name('backup.restore');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:settings', 'role:super_admin'])->prefix('settings/system')->name('settings.system.')->group(function () {
    Route::post('maintenance', [SystemOperationsController::class, 'maintenance'])->name('maintenance');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:system', 'role:admin,super_admin', 'throttle:60,1'])->prefix('/admin/settings/integrations')->name('admin.settings.integrations.')->group(function () {
    Route::get('/', [IntegrationController::class, 'index'])->name('index');
    Route::post('/', [IntegrationController::class, 'store'])->name('store');
    Route::get('/{name}', [IntegrationController::class, 'show'])->name('show');
    Route::post('/{name}', [IntegrationController::class, 'update'])->name('update');
    Route::post('/{name}/accounts', [IntegrationController::class, 'storeAccount'])->name('accounts.store');
    Route::patch('/{name}/toggle', [IntegrationController::class, 'toggle'])->name('toggle');
    Route::post('/{name}/test', [IntegrationController::class, 'testConnection'])->name('test');
    Route::delete('/{name}', [IntegrationController::class, 'destroy'])->name('destroy');
    Route::post('/google-business-profile/reviews/sync', [IntegrationController::class, 'syncGoogleReviews'])->name('google-business-profile.sync-reviews');
    Route::post('/whatsapp/click-to-chat', [IntegrationController::class, 'updateClickToChat'])->name('whatsapp.click-to-chat');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout'])->group(function () {
    Route::get('/workspace/search', WorkspaceSearchController::class)->name('workspace.search');
});

Route::middleware(['auth', 'active', 'auto.logout'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
