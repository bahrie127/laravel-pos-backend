<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

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
});
