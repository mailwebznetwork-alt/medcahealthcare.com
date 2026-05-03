<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleSurfaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileModuleAccessController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified', 'module:dashboard'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'verified', 'module:site_architect'])->group(function () {
    Route::get('/site-architect', [ModuleSurfaceController::class, 'show'])
        ->defaults('momModule', 'site_architect')
        ->name('modules.site-architect');
});

Route::middleware(['auth', 'verified', 'module:operations'])->group(function () {
    Route::get('/operations', [ModuleSurfaceController::class, 'show'])
        ->defaults('momModule', 'operations')
        ->name('modules.operations');
});

Route::middleware(['auth', 'verified', 'module:marketing'])->group(function () {
    Route::get('/marketing', [ModuleSurfaceController::class, 'show'])
        ->defaults('momModule', 'marketing')
        ->name('modules.marketing');
});

Route::middleware(['auth', 'verified', 'module:growth_center'])->group(function () {
    Route::get('/growth-center', [ModuleSurfaceController::class, 'show'])
        ->defaults('momModule', 'growth_center')
        ->name('modules.growth-center');
});

Route::middleware(['auth', 'verified', 'module:user_management'])->group(function () {
    Route::get('/user-management', [ModuleSurfaceController::class, 'show'])
        ->defaults('momModule', 'user_management')
        ->name('modules.user-management');
});

Route::middleware(['auth', 'verified', 'module:security'])->group(function () {
    Route::get('/security', [ModuleSurfaceController::class, 'show'])
        ->defaults('momModule', 'security')
        ->name('modules.security');
});

Route::middleware(['auth', 'verified', 'module:settings'])->group(function () {
    Route::get('/settings', SettingsController::class)->name('settings.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/module-access', [ProfileModuleAccessController::class, 'update'])->name('profile.module-access.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
