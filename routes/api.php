<?php

use App\Http\Controllers\Admin\Growth\CompetitorController;
use App\Http\Controllers\Api\Operations\ServiceCategoryApiController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PaymentNotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/leads', [LeadController::class, 'store'])->middleware('throttle:api_leads');

Route::prefix('admin/operations/service-categories')
    ->middleware(['auth:sanctum', 'active', 'module:operations'])
    ->group(function () {
        Route::get('/', [ServiceCategoryApiController::class, 'index']);
        Route::get('/picker', [ServiceCategoryApiController::class, 'picker']);
        Route::get('/{service_category}', [ServiceCategoryApiController::class, 'show']);
        Route::get('/{service_category}/services', [ServiceCategoryApiController::class, 'services']);
    });

Route::post('/payments/notify', [PaymentNotificationController::class, 'store'])
    ->middleware(['throttle:payments_notify', 'payment.ingest.signature']);

Route::prefix('admin/growth/competitors')->middleware(['auth:sanctum', 'active', 'module:growth_center'])->group(function () {
    Route::get('/', [CompetitorController::class, 'index']);
    Route::post('/bulk', [CompetitorController::class, 'bulkStore']);
    Route::post('/compare', [CompetitorController::class, 'compare']);
    Route::get('/summary', [CompetitorController::class, 'summary']);
});
