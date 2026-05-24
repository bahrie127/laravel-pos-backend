<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('home');
    }

    return view('pages.auth.login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('home', [DashboardController::class, 'index'])->name('home');

    Route::resource('user', UserController::class);

    Route::delete('product/bulk', [ProductController::class, 'bulkDestroy'])->name('product.bulk-destroy');
    Route::resource('product', ProductController::class);

    Route::get('order/export', [OrderController::class, 'export'])->name('order.export');
    Route::get('order/{order}/receipt', [OrderController::class, 'receipt'])->name('order.receipt');
    Route::get('order/{order}/invoice-pdf', [OrderController::class, 'invoicePdf'])->name('order.invoice-pdf');
    Route::resource('order', OrderController::class)->only(['index', 'show', 'destroy']);
    Route::resource('categories', CategoryController::class);

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });
});
