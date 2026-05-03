<?php

use App\Http\Controllers\Careers\CareersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleSurfaceController;
use App\Http\Controllers\Operations\JobPortal\ApplicationController;
use App\Http\Controllers\Operations\JobPortal\JobPortalDashboardController;
use App\Http\Controllers\Operations\JobPortal\VacancyController;
use App\Http\Controllers\Operations\OperationsHubController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserManagement\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/careers', [CareersController::class, 'index'])->name('careers.index');
Route::get('/careers/{slug}', [CareersController::class, 'show'])->name('careers.show');
Route::post('/careers/{slug}/apply', [CareersController::class, 'storeApplication'])
    ->middleware('throttle:10,1')
    ->name('careers.apply');

Route::middleware(['auth', 'active', 'verified', 'module:dashboard'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'active', 'verified', 'module:site_architect'])->group(function () {
    Route::get('/site-architect', [ModuleSurfaceController::class, 'show'])
        ->defaults('momModule', 'site_architect')
        ->name('modules.site-architect');
});

Route::middleware(['auth', 'active', 'verified', 'module:operations'])->group(function () {
    Route::get('/operations', OperationsHubController::class)->name('modules.operations');

    Route::prefix('operations/job-portal')->name('operations.job-portal.')->group(function () {
        Route::get('/', JobPortalDashboardController::class)->name('index');
        Route::post('vacancies/{vacancy}/duplicate', [VacancyController::class, 'duplicate'])->name('vacancies.duplicate');
        Route::resource('vacancies', VacancyController::class);
        Route::resource('applications', ApplicationController::class)->only(['index', 'show', 'update']);
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
