<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashSessionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PromoController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
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

    Route::apiResource('products', ProductController::class)
        ->only(['index', 'show', 'store', 'update']);
    // Multipart updates from mobile clients can't use PUT cleanly, so we
    // accept POST + _method=PUT on the same path. apiResource already
    // exposes the canonical PUT for completeness.
    Route::post('products/{product}', [ProductController::class, 'update'])
        ->whereNumber('product');
    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
    Route::get('orders/kasir/{kasir_id}', [OrderController::class, 'getByKasirId']);
    Route::post('orders/{order}/refund', [RefundController::class, 'store'])
        ->whereNumber('order');

    // Manage Users — admin/owner only (policy gated).
    Route::apiResource('users', UserController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names([
            'index' => 'api.users.index',
            'store' => 'api.users.store',
            'update' => 'api.users.update',
            'destroy' => 'api.users.destroy',
        ]);

    Route::get('list-categories', [CategoryController::class, 'index']);
    // Mobile bisa CRUD kategori — admin/owner only (policy gated).
    Route::apiResource('categories', CategoryController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names([
            'index' => 'api.categories.index',
            'store' => 'api.categories.store',
            'update' => 'api.categories.update',
            'destroy' => 'api.categories.destroy',
        ]);

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
        // Admin/owner force-close shift orang lain (kasir lupa tutup).
        Route::post('{id}/force-close', [CashSessionController::class, 'forceClose'])
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
