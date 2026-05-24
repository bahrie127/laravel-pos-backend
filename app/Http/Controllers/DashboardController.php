<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $yesterday = $today->copy()->subDay();

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

        // Sales trend last 7 days (fill missing days with 0)
        $sevenDaysAgo = $today->copy()->subDays(6);
        $rawTrend = Order::selectRaw('DATE(transaction_time) as date, SUM(total_price) as total, COUNT(*) as orders')
            ->where('transaction_time', '>=', $sevenDaysAgo->copy()->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $salesTrend = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $sevenDaysAgo->copy()->addDays($i);
            $key = $d->toDateString();
            $row = $rawTrend->get($key);
            $salesTrend[] = [
                'date' => $key,
                'label' => $d->translatedFormat('d M'),
                'total' => $row ? (float) $row->total : 0,
                'orders' => $row ? (int) $row->orders : 0,
            ];
        }

        // Top 5 products
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
            'salesTrend',
            'topProducts',
            'recentOrders',
            'lowStock',
            'paymentBreakdown'
        ));
    }
}
