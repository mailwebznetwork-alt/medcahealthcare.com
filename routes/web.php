<?php

use App\Http\Controllers\Careers\CareersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Growth\AeoController;
use App\Http\Controllers\Growth\CompetitorPageController;
use App\Http\Controllers\Growth\GeoController;
use App\Http\Controllers\Growth\SeoController;
use App\Http\Controllers\Growth\WarRoomController;
use App\Http\Controllers\MarketingEmailOpenController;
use App\Http\Controllers\ModuleSurfaceController;
use App\Http\Controllers\Operations\JobPortal\ApplicationController;
use App\Http\Controllers\Operations\JobPortal\JobPortalDashboardController;
use App\Http\Controllers\Operations\JobPortal\VacancyController;
use App\Http\Controllers\Operations\OperationsHubController;
use App\Http\Controllers\Operations\PinCodes\PinCodeController;
use App\Http\Controllers\Operations\PinCodes\PinCodeImportController;
use App\Http\Controllers\Operations\Services\ServiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\IntegrationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserManagement\UserController;
use App\Models\Blog;
use App\Models\Lead;
use App\Models\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SeoController::class, 'robotsTxt'])->name('public.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemapXml'])->name('public.sitemap');
Route::get('/llm.txt', [AeoController::class, 'llmTxt'])->name('public.llm');
Route::get('/ai-discovery', [AeoController::class, 'discovery'])->name('public.ai-discovery');

Route::get('/', function () {
    return view('home');
});

Route::get('/t/mail/{token}/open.gif', [MarketingEmailOpenController::class, 'pixel'])
    ->middleware('throttle:120,1')
    ->name('marketing.email-open-pixel');

Route::get('/careers', [CareersController::class, 'index'])->name('careers.index');
Route::get('/careers/{slug}', [CareersController::class, 'show'])->name('careers.show');
Route::post('/careers/{slug}/apply', [CareersController::class, 'storeApplication'])
    ->middleware('throttle:10,1')
    ->name('careers.apply');

Route::get('/p/{page:slug}', function (Page $page) {
    abort_unless($page->is_active, 404);

    return view('layouts.app', ['page' => $page]);
})->name('pages.public');

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

            return view('layouts.app', ['page' => $page]);
        })->name('pages.preview');

        Route::view('/blogs', 'site-architect.blogs-shell')->name('blogs.index');
        Route::get('/blogs/{blog}/preview', function (Blog $blog) {
            Gate::authorize('view', $blog);

            return view('layouts.app', ['blog' => $blog]);
        })->name('blogs.preview');

        Route::view('/block-factory', 'site-architect.block-factory-shell')->name('block-factory.index');

        Route::view('/media', 'site-architect.media-library-shell')->name('media.index');
    });
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:operations', 'role:manager,admin,super_admin'])->group(function () {
    Route::get('/operations', OperationsHubController::class)->name('modules.operations');

    Route::prefix('operations/job-portal')->name('operations.job-portal.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('operations.job-portal.overview');
        })->name('index');
        Route::get('overview', JobPortalDashboardController::class)->name('overview');
        Route::post('vacancies/{vacancy}/duplicate', [VacancyController::class, 'duplicate'])->name('vacancies.duplicate');
        Route::resource('vacancies', VacancyController::class);
        Route::resource('applications', ApplicationController::class)->only(['index', 'show', 'update']);
    });

    Route::prefix('operations/services')->name('operations.services.')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::get('create', [ServiceController::class, 'create'])->name('create');
        Route::post('/', [ServiceController::class, 'store'])->name('store');
        Route::get('{service}/duplicate', [ServiceController::class, 'duplicate'])->name('duplicate');
        Route::get('{service}/preview', [ServiceController::class, 'preview'])->name('preview');
        Route::get('{service}/edit', [ServiceController::class, 'edit'])->name('edit');
        Route::put('{service}', [ServiceController::class, 'update'])->name('update');
        Route::delete('{service}', [ServiceController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('operations/pin-codes')->name('operations.pin-codes.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('operations.pin-codes.overview');
        })->name('index');
        Route::get('overview', [PinCodeController::class, 'overview'])->name('overview');
        Route::get('directory', [PinCodeController::class, 'directory'])->name('directory');
        Route::get('bulk-import', [PinCodeImportController::class, 'create'])->name('bulk-import');
        Route::post('bulk-import/preview', [PinCodeImportController::class, 'preview'])->name('bulk-import.preview');
        Route::post('bulk-import/confirm', [PinCodeImportController::class, 'confirm'])->name('bulk-import.confirm');
        Route::post('bulk-import/cancel', [PinCodeImportController::class, 'cancel'])->name('bulk-import.cancel');
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
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:marketing', 'role:manager,admin,super_admin'])->group(function () {
    Route::view('/marketing', 'marketing.dashboard-shell')->name('modules.marketing');
});

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:growth_center', 'role:viewer,editor,manager,admin,super_admin'])->group(function () {
    Route::get('/growth-center', function () {
        return redirect()->route('growth-center.competitors.index');
    })->name('modules.growth-center');
    Route::get('/growth-center/competitors', CompetitorPageController::class)->name('growth-center.competitors.index');
    Route::post('/growth-center/competitors', [CompetitorPageController::class, 'store'])->name('growth-center.competitors.store');
    Route::post('/growth-center/competitors/bulk', [CompetitorPageController::class, 'bulkStore'])->name('growth-center.competitors.bulk-store');
    Route::post('/growth-center/competitors/compare', [CompetitorPageController::class, 'compare'])->name('growth-center.competitors.compare');
    Route::post('/growth-center/competitors/keywords', [CompetitorPageController::class, 'storeKeyword'])->name('growth-center.competitors.keywords.store');
    Route::post('/growth-center/competitors/keywords/bulk', [CompetitorPageController::class, 'storeKeywordsBulk'])->name('growth-center.competitors.keywords.bulk-store');
    Route::post('/growth-center/competitors/tracking', [CompetitorPageController::class, 'storeTracking'])->name('growth-center.competitors.tracking.store');
    Route::post('/growth-center/competitors/leads', [CompetitorPageController::class, 'storeLead'])->name('growth-center.competitors.leads.store');
    Route::delete('/growth-center/competitors/{competitor}', [CompetitorPageController::class, 'destroy'])->name('growth-center.competitors.destroy');

    Route::prefix('growth-center')->group(function () {
        Route::prefix('seo')->group(function () {
            Route::get('entity', [SeoController::class, 'entity'])->name('growth-center.seo.entity');
            Route::post('entity', [SeoController::class, 'storeEntity'])->name('growth-center.seo.entity.store');
            Route::get('technical', [SeoController::class, 'technical'])->name('growth-center.seo.technical');
            Route::post('technical', [SeoController::class, 'storeTechnical'])->name('growth-center.seo.technical.store');
        });

        Route::prefix('aeo')->group(function () {
            Route::get('/', [AeoController::class, 'index'])->name('growth-center.aeo.index');
            Route::post('/', [AeoController::class, 'store'])->name('growth-center.aeo.store');
        });

        Route::prefix('geo')->group(function () {
            Route::get('location', [GeoController::class, 'location'])->name('growth-center.geo.location');
            Route::post('location', [GeoController::class, 'storeLocation'])->name('growth-center.geo.location.store');
            Route::get('pincodes', [GeoController::class, 'pincodes'])->name('growth-center.geo.pincodes');
            Route::post('pincode', [GeoController::class, 'storePincode'])->name('growth-center.geo.pincode.store');
            Route::put('pincode/{id}', [GeoController::class, 'updatePincode'])->name('growth-center.geo.pincode.update');
        });

        Route::prefix('war-room')->group(function () {
            Route::get('dashboard', [WarRoomController::class, 'dashboard'])->name('growth-center.war-room.dashboard');
            Route::get('intercepts', [WarRoomController::class, 'intercepts'])->name('growth-center.war-room.intercepts');
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

Route::middleware(['auth', 'active', 'verified', 'auto.logout', 'module:settings', 'role:admin,super_admin'])->group(function () {
    Route::get('/settings', SettingsController::class)->name('settings.index');
});

Route::middleware(['auth', 'admin', 'throttle:60,1'])->prefix('/admin/settings/integrations')->name('admin.settings.integrations.')->group(function () {
    Route::get('/', [IntegrationController::class, 'index'])->name('index');
    Route::post('/', [IntegrationController::class, 'store'])->name('store');
    Route::get('/{name}', [IntegrationController::class, 'show'])->name('show');
    Route::post('/{name}', [IntegrationController::class, 'update'])->name('update');
    Route::post('/{name}/accounts', [IntegrationController::class, 'storeAccount'])->name('accounts.store');
    Route::patch('/{name}/toggle', [IntegrationController::class, 'toggle'])->name('toggle');
    Route::post('/{name}/test', [IntegrationController::class, 'testConnection'])->name('test');
    Route::delete('/{name}', [IntegrationController::class, 'destroy'])->name('destroy');
    Route::post('/google-business-profile/reviews/sync', [IntegrationController::class, 'syncGoogleReviews'])->name('google-business-profile.sync-reviews');
});

Route::middleware(['auth', 'active', 'auto.logout'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
