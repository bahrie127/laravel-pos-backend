<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashSessionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PromoController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Privacy policy — referenced from the Play Store listing + in-app Setting tile.
Route::get('privacy', fn () => response()
    ->view('legal.privacy')
    ->header('Content-Type', 'text/html; charset=UTF-8'));

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::delete('account', [AuthController::class, 'deleteAccount']);

    // Backward-compat: /api/user (legacy)
    Route::get('user', [AuthController::class, 'me']);

    Route::apiResource('products', ProductController::class)->only(['index', 'show', 'store']);
    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
    Route::get('orders/kasir/{kasir_id}', [OrderController::class, 'getByKasirId']);

    Route::get('list-categories', [CategoryController::class, 'index']);
    Route::apiResource('categories', CategoryController::class)->only(['index']);

    Route::prefix('reports')->group(function () {
        Route::get('summary', [ReportController::class, 'summary']);
        Route::get('product-sales', [ReportController::class, 'productSales']);
        Route::get('close-cashier', [ReportController::class, 'closeCashier']);
    });

    Route::prefix('cash-sessions')->group(function () {
        Route::get('/', [CashSessionController::class, 'index']);
        Route::get('current', [CashSessionController::class, 'current']);
        Route::post('open', [CashSessionController::class, 'open']);
        Route::get('{id}', [CashSessionController::class, 'show'])
            ->whereNumber('id');
        Route::get('{id}/summary', [CashSessionController::class, 'summary'])
            ->whereNumber('id');
        Route::post('{id}/close', [CashSessionController::class, 'close'])
            ->whereNumber('id');
    });

    Route::prefix('promos')->group(function () {
        Route::get('/', [PromoController::class, 'index']);
        Route::post('/', [PromoController::class, 'store']);
        Route::post('apply', [PromoController::class, 'apply']);
        Route::get('{promo}', [PromoController::class, 'show']);
        Route::put('{promo}', [PromoController::class, 'update']);
        Route::post('{promo}/toggle', [PromoController::class, 'toggle']);
        Route::delete('{promo}', [PromoController::class, 'destroy']);
    });
});
