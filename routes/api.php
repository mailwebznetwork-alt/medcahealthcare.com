<?php

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Growth\CompetitorController;
use Illuminate\Support\Facades\Route;

Route::post('/leads', [LeadController::class, 'store'])->middleware('throttle:60,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('admin/growth/competitors')
    ->group(function (): void {
        Route::get('/', [CompetitorController::class, 'index']);
        Route::post('/bulk', [CompetitorController::class, 'bulkStore']);
        Route::post('/compare', [CompetitorController::class, 'compare']);
        Route::get('/summary', [CompetitorController::class, 'summary']);
    });
