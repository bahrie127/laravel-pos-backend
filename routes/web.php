<?php

use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('home');
    }

    return view('pages.auth.login');
});

Route::get('/privacy', fn () => response()
    ->view('legal.privacy')
    ->header('Content-Type', 'text/html; charset=UTF-8'))
    ->name('privacy');

Route::middleware(['auth', 'cache.headers:no_store;no_cache;must_revalidate;max_age=0'])->group(function () {
    Route::get('home', [DashboardController::class, 'index'])->name('home');

    Route::resource('user', UserController::class);

    Route::delete('product/bulk', [ProductController::class, 'bulkDestroy'])->name('product.bulk-destroy');
    Route::resource('product', ProductController::class);

    Route::get('order/export', [OrderController::class, 'export'])->name('order.export');
    Route::get('order/{order}/receipt', [OrderController::class, 'receipt'])->name('order.receipt');
    Route::get('order/{order}/invoice-pdf', [OrderController::class, 'invoicePdf'])->name('order.invoice-pdf');
    Route::resource('order', OrderController::class)->only(['index', 'show', 'destroy']);
    Route::resource('categories', CategoryController::class);

    Route::post('promo/{promo}/toggle', [PromoController::class, 'toggle'])->name('promo.toggle');
    Route::resource('promo', PromoController::class)->except(['show']);

    Route::prefix('cash-sessions')->name('cash-session.')->group(function () {
        Route::get('/', [CashSessionController::class, 'index'])->name('index');
        Route::post('/open', [CashSessionController::class, 'open'])->name('open');
        Route::get('/{cashSession}', [CashSessionController::class, 'show'])->name('show');
        Route::post('/{cashSession}/close', [CashSessionController::class, 'close'])->name('close');
        Route::post('/{cashSession}/force-close', [CashSessionController::class, 'forceClose'])->name('force-close');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/summary', [ReportController::class, 'summary'])->name('summary');
        Route::get('/product-sales', [ReportController::class, 'productSales'])->name('product-sales');
        Route::get('/close-cashier', [ReportController::class, 'closeCashier'])->name('close-cashier');
        Route::get('/promo-usage', [ReportController::class, 'promoUsage'])->name('promo-usage');
        Route::get('/sales-analytics', [ReportController::class, 'salesAnalytics'])->name('sales-analytics');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
    });
});
