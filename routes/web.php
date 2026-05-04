<?php

use App\Http\Controllers\Careers\CareersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleSurfaceController;
use App\Http\Controllers\Operations\JobPortal\ApplicationController;
use App\Http\Controllers\Operations\JobPortal\JobPortalDashboardController;
use App\Http\Controllers\Operations\JobPortal\VacancyController;
use App\Http\Controllers\Operations\OperationsHubController;
use App\Http\Controllers\Operations\PinCodes\PinCodeController;
use App\Http\Controllers\Operations\PinCodes\PinCodeImportController;
use App\Http\Controllers\Operations\Services\ServiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserManagement\UserController;
use App\Models\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/careers', [CareersController::class, 'index'])->name('careers.index');
Route::get('/careers/{slug}', [CareersController::class, 'show'])->name('careers.show');
Route::post('/careers/{slug}/apply', [CareersController::class, 'storeApplication'])
    ->middleware('throttle:10,1')
    ->name('careers.apply');

Route::get('/p/{page:slug}', function (Page $page) {
    abort_unless($page->is_active, 404);

    return view('layouts.app', ['page' => $page]);
})->name('pages.public');

Route::middleware(['auth', 'active', 'verified', 'module:dashboard'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'active', 'verified', 'module:site_architect'])->group(function () {
    Route::get('/site-architect', function () {
        return redirect()->route('site-architect.pages.index');
    })->name('modules.site-architect');

    Route::prefix('site-architect')->name('site-architect.')->group(function () {
        Route::view('/pages', 'site-architect.pages-shell')->name('pages.index');
        Route::get('/pages/{page}/preview', function (Page $page) {
            Gate::authorize('view', $page);

            return view('layouts.app', ['page' => $page]);
        })->name('pages.preview');
    });
});

Route::middleware(['auth', 'active', 'verified', 'module:operations'])->group(function () {
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
});

Route::middleware(['auth', 'active', 'verified', 'module:marketing'])->group(function () {
    Route::get('/marketing', [ModuleSurfaceController::class, 'show'])
        ->defaults('momModule', 'marketing')
        ->name('modules.marketing');
});

Route::middleware(['auth', 'active', 'verified', 'module:growth_center'])->group(function () {
    Route::get('/growth-center', [ModuleSurfaceController::class, 'show'])
        ->defaults('momModule', 'growth_center')
        ->name('modules.growth-center');
});

Route::middleware(['auth', 'active', 'verified', 'module:user_management'])->prefix('user-management')->name('user-management.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::put('{user}', [UserController::class, 'update'])->name('update');
    Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
    Route::patch('{user}/activate', [UserController::class, 'activate'])->name('activate');
    Route::patch('{user}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
});

Route::middleware(['auth', 'active', 'verified', 'module:security'])->group(function () {
    Route::get('/security', [ModuleSurfaceController::class, 'show'])
        ->defaults('momModule', 'security')
        ->name('modules.security');
});

Route::middleware(['auth', 'active', 'verified', 'module:settings'])->group(function () {
    Route::get('/settings', SettingsController::class)->name('settings.index');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
