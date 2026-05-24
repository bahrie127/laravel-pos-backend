<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $yesterday = $today->copy()->subDay();
        $startOfWeek = $today->copy()->startOfWeek();
        $startOfMonth = $today->copy()->startOfMonth();

        $revenueToday = (float) Order::whereDate('transaction_time', $today)->sum('total_price');
        $revenueYesterday = (float) Order::whereDate('transaction_time', $yesterday)->sum('total_price');
        $ordersToday = Order::whereDate('transaction_time', $today)->count();

        $revenueDelta = null;
        if ($revenueYesterday > 0) {
            $revenueDelta = round((($revenueToday - $revenueYesterday) / $revenueYesterday) * 100, 1);
        }

        $stats = [
            'revenue_today' => $revenueToday,
            'revenue_delta' => $revenueDelta,
            'orders_today' => $ordersToday,
            'total_products' => Product::count(),
            'active_users' => User::count(),
        ];

        $quickStats = [
            'revenue_week' => (float) Order::where('transaction_time', '>=', $startOfWeek)->sum('total_price'),
            'orders_week' => Order::where('transaction_time', '>=', $startOfWeek)->count(),
            'revenue_month' => (float) Order::where('transaction_time', '>=', $startOfMonth)->sum('total_price'),
            'orders_month' => Order::where('transaction_time', '>=', $startOfMonth)->count(),
            'avg_order_value' => (float) Order::avg('total_price'),
            'total_orders_lifetime' => Order::count(),
            'total_categories' => Category::count(),
            'out_of_stock' => Product::where('stock', 0)->count(),
        ];

        $topProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total_price) as total_revenue'))
            ->with('product:id,name,price,image')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        $recentOrders = Order::with('kasir:id,name')
            ->latest('transaction_time')
            ->limit(8)
            ->get();

        $lowStock = Product::where('stock', '<', 5)
            ->orderBy('stock')
            ->limit(8)
            ->get(['id', 'name', 'stock']);

        $paymentBreakdown = Order::whereDate('transaction_time', $today)
            ->select('payment_method', DB::raw('SUM(total_price) as total'), DB::raw('COUNT(*) as orders'))
            ->groupBy('payment_method')
            ->get();

        return view('pages.dashboard', compact(
            'stats',
            'quickStats',
            'topProducts',
            'recentOrders',
            'lowStock',
            'paymentBreakdown'
        ));
    }
}
