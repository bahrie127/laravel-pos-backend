<?php

namespace App\Providers;

use App\Models\CashSession;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promo;
use App\Models\User;
use App\Policies\CashSessionPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PromoPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Product::class => ProductPolicy::class,
        CashSession::class => CashSessionPolicy::class,
        Category::class => CategoryPolicy::class,
        Order::class => OrderPolicy::class,
        Promo::class => PromoPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Gate untuk akses reports (tidak terikat model)
        Gate::define('view-reports', fn (User $user) => $user->isAdmin());
    }
}
