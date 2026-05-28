<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promo;
use App\Models\User;
use App\Reports\ReportExporter;
use App\Support\DateRangeResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! $request->user()?->can('view-reports')) {
                abort(403, 'Anda tidak memiliki akses ke laporan.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        return view('pages.reports.index');
    }

    public function summary(Request $request)
    {
        $range = DateRangeResolver::fromRequest($request);

        $base = $this->applyOrderFilters(Order::query()->whereBetween('transaction_time', [$range->from, $range->to]), $request);

        $summary = [
            'total_revenue' => (float) (clone $base)->sum('total_price'),
            'total_orders' => (clone $base)->count(),
            'total_items' => (int) (clone $base)->sum('total_item'),
            'avg_per_order' => (float) (clone $base)->avg('total_price'),
        ];

        $dailyRevenue = (clone $base)
            ->selectRaw('DATE(transaction_time) as date, SUM(total_price) as total, COUNT(*) as orders')
            ->groupBy('date')->orderBy('date')->get();

        $paymentBreakdown = (clone $base)
            ->whereNotNull('payment_method')
            ->selectRaw('payment_method, SUM(total_price) as total, COUNT(*) as orders')
            ->groupBy('payment_method')->get();

        $kasirPerformance = (clone $base)
            ->join('users', 'orders.kasir_id', '=', 'users.id')
            ->selectRaw('users.name as kasir_name, COUNT(*) as order_count, SUM(orders.total_price) as total_revenue')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_revenue')->get();

        return view('pages.reports.summary', [
            'summary' => $summary,
            'dailyRevenue' => $dailyRevenue,
            'paymentBreakdown' => $paymentBreakdown,
            'kasirPerformance' => $kasirPerformance,
            'range' => $range,
            'from' => $range->from,
            'to' => $range->to,
            'kasirList' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function productSales(Request $request)
    {
        $range = DateRangeResolver::fromRequest($request);

        $items = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('orders.transaction_time', [$range->from, $range->to])
            ->when($request->filled('category_id'), fn ($q) => $q->where('products.category_id', $request->category_id))
            ->when($request->filled('kasir_id'), fn ($q) => $q->where('orders.kasir_id', $request->kasir_id))
            ->selectRaw('
                products.id,
                products.name,
                COALESCE(categories.name, products.category, "—") as cat,
                SUM(order_items.quantity) as qty_sold,
                SUM(order_items.total_price) as revenue
            ')
            ->groupBy('products.id', 'products.name', 'cat')
            ->orderByDesc('revenue')
            ->get();

        $totalRevenue = (float) $items->sum('revenue');
        $items = $items->map(function ($r) use ($totalRevenue) {
            $r->percentage = $totalRevenue > 0 ? round(($r->revenue / $totalRevenue) * 100, 1) : 0;
            return $r;
        });

        return view('pages.reports.product-sales', [
            'items' => $items,
            'totalRevenue' => $totalRevenue,
            'range' => $range,
            'from' => $range->from,
            'to' => $range->to,
            'kasirList' => User::orderBy('name')->get(['id', 'name']),
            'categoryList' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function closeCashier(Request $request)
    {
        $range = DateRangeResolver::fromRequest($request);

        $kasirId = $request->kasir_id;
        $kasirList = User::orderBy('name')->get(['id', 'name']);

        $rows = collect();
        $totals = ['count' => 0, 'revenue' => 0];

        if ($kasirId) {
            $query = Order::query()
                ->where('kasir_id', $kasirId)
                ->whereBetween('transaction_time', [$range->from, $range->to]);

            $rows = (clone $query)
                ->selectRaw('payment_method, COUNT(*) as orders, SUM(total_price) as total')
                ->groupBy('payment_method')->get();

            $totals['count'] = (clone $query)->count();
            $totals['revenue'] = (float) (clone $query)->sum('total_price');
        }

        return view('pages.reports.close-cashier', [
            'rows' => $rows,
            'totals' => $totals,
            'kasirList' => $kasirList,
            'selectedKasir' => $kasirId ? User::find($kasirId) : null,
            'range' => $range,
            'from' => $range->from,
            'to' => $range->to,
        ]);
    }

    public function promoUsage(Request $request)
    {
        $range = DateRangeResolver::fromRequest($request);

        $base = Order::query()
            ->whereBetween('transaction_time', [$range->from, $range->to])
            ->whereNotNull('promo_id');

        $totals = [
            'orders_using_promo' => (clone $base)->count(),
            'total_discount' => (int) (clone $base)->sum('discount_amount'),
            'gross_revenue' => (int) (clone $base)->sum(DB::raw('total_price + discount_amount')),
            'net_revenue' => (int) (clone $base)->sum('total_price'),
        ];

        $allOrdersInRange = Order::whereBetween('transaction_time', [$range->from, $range->to])->count();
        $totals['promo_usage_rate'] = $allOrdersInRange > 0
            ? round(($totals['orders_using_promo'] / $allOrdersInRange) * 100, 1)
            : 0.0;

        $perPromo = Order::query()
            ->join('promos', 'orders.promo_id', '=', 'promos.id')
            ->whereBetween('orders.transaction_time', [$range->from, $range->to])
            ->selectRaw('
                promos.id, promos.name, promos.code, promos.type, promos.value, promos.active,
                COUNT(*) as usage_count,
                SUM(orders.discount_amount) as total_discount,
                SUM(orders.total_price) as net_revenue
            ')
            ->groupBy('promos.id', 'promos.name', 'promos.code', 'promos.type', 'promos.value', 'promos.active')
            ->orderByDesc('total_discount')
            ->get();

        $unusedPromos = Promo::whereDoesntHave('orders', function ($q) use ($range) {
            $q->whereBetween('transaction_time', [$range->from, $range->to]);
        })->get();

        return view('pages.reports.promo-usage', compact('totals', 'perPromo', 'unusedPromos', 'range') + [
            'from' => $range->from,
            'to' => $range->to,
        ]);
    }

    public function salesAnalytics(Request $request)
    {
        $range = DateRangeResolver::fromRequest($request, '30d');

        $base = $this->applyOrderFilters(Order::query()->whereBetween('transaction_time', [$range->from, $range->to]), $request);

        // Current period stats
        $stats = [
            'revenue' => (float) (clone $base)->sum('total_price'),
            'orders' => (clone $base)->count(),
            'items' => (int) (clone $base)->sum('total_item'),
        ];
        $stats['avg_ticket'] = $stats['orders'] > 0 ? $stats['revenue'] / $stats['orders'] : 0;

        // Previous period for delta comparison
        $prevBase = $this->applyOrderFilters(
            Order::query()->whereBetween('transaction_time', [$range->previousFrom, $range->previousTo]),
            $request
        );
        $prevStats = [
            'revenue' => (float) (clone $prevBase)->sum('total_price'),
            'orders' => (clone $prevBase)->count(),
            'items' => (int) (clone $prevBase)->sum('total_item'),
        ];
        $prevStats['avg_ticket'] = $prevStats['orders'] > 0 ? $prevStats['revenue'] / $prevStats['orders'] : 0;

        $delta = [];
        foreach ($stats as $key => $val) {
            $delta[$key] = $prevStats[$key] > 0
                ? round((($val - $prevStats[$key]) / $prevStats[$key]) * 100, 1)
                : null;
        }

        // Daily trend (current + previous overlay)
        $dailyCurrent = (clone $base)
            ->selectRaw('DATE(transaction_time) as date, SUM(total_price) as total')
            ->groupBy('date')->orderBy('date')->get();

        $dailyPrevious = (clone $prevBase)
            ->selectRaw('DATE(transaction_time) as date, SUM(total_price) as total')
            ->groupBy('date')->orderBy('date')->get();

        // Hourly distribution
        $hourly = (clone $base)
            ->selectRaw('HOUR(transaction_time) as hour, COUNT(*) as orders, SUM(total_price) as total')
            ->groupBy('hour')->orderBy('hour')->get()
            ->keyBy('hour');

        $hourlyFull = collect(range(0, 23))->map(fn ($h) => [
            'hour' => sprintf('%02d:00', $h),
            'orders' => (int) ($hourly[$h]->orders ?? 0),
            'total' => (int) ($hourly[$h]->total ?? 0),
        ]);

        // Day-of-week (1=Monday … 7=Sunday using ISO)
        $dowRaw = (clone $base)
            ->selectRaw('DAYOFWEEK(transaction_time) as dow, COUNT(*) as orders, SUM(total_price) as total')
            ->groupBy('dow')->get()->keyBy('dow');

        $dayNames = [1 => 'Min', 2 => 'Sen', 3 => 'Sel', 4 => 'Rab', 5 => 'Kam', 6 => 'Jum', 7 => 'Sab'];
        $dow = collect(range(1, 7))->map(fn ($d) => [
            'day' => $dayNames[$d],
            'orders' => (int) ($dowRaw[$d]->orders ?? 0),
            'total' => (int) ($dowRaw[$d]->total ?? 0),
        ]);

        // Top categories
        $topCategories = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('orders.transaction_time', [$range->from, $range->to])
            ->when($request->filled('kasir_id'), fn ($q) => $q->where('orders.kasir_id', $request->kasir_id))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('orders.payment_method', $request->payment_method))
            ->selectRaw('COALESCE(categories.name, products.category, "Tanpa Kategori") as cat,
                SUM(order_items.quantity) as qty, SUM(order_items.total_price) as revenue')
            ->groupBy('cat')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        // Payment method
        $paymentBreakdown = (clone $base)
            ->whereNotNull('payment_method')
            ->selectRaw('payment_method, SUM(total_price) as total, COUNT(*) as orders')
            ->groupBy('payment_method')->get();

        // Peak hour
        $peakHour = $hourlyFull->sortByDesc('total')->first();
        $peakDay = $dow->sortByDesc('total')->first();

        return view('pages.reports.sales-analytics', [
            'stats' => $stats,
            'prevStats' => $prevStats,
            'delta' => $delta,
            'dailyCurrent' => $dailyCurrent,
            'dailyPrevious' => $dailyPrevious,
            'hourlyFull' => $hourlyFull,
            'dow' => $dow,
            'topCategories' => $topCategories,
            'paymentBreakdown' => $paymentBreakdown,
            'peakHour' => $peakHour,
            'peakDay' => $peakDay,
            'range' => $range,
            'from' => $range->from,
            'to' => $range->to,
            'kasirList' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function inventory(Request $request)
    {
        $categoryId = $request->category_id;
        $stockStatus = $request->stock_status;

        $products = Product::query()
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('orders', function ($j) {
                $j->on('orders.id', '=', 'order_items.order_id')
                  ->where('orders.status', Order::STATUS_PAID);
            })
            ->select(
                'products.id', 'products.name', 'products.stock', 'products.price', 'products.image',
                DB::raw('COALESCE(categories.name, products.category, "—") as cat_name'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_sold'),
                DB::raw('MAX(orders.transaction_time) as last_sold_at')
            )
            ->when($categoryId, fn ($q) => $q->where('products.category_id', $categoryId))
            ->when($stockStatus === 'out', fn ($q) => $q->where('products.stock', '<=', 0))
            ->when($stockStatus === 'low', fn ($q) => $q->whereBetween('products.stock', [1, 4]))
            ->when($stockStatus === 'in', fn ($q) => $q->where('products.stock', '>=', 5))
            ->groupBy('products.id', 'products.name', 'products.stock', 'products.price', 'products.image', 'cat_name')
            ->orderBy('products.stock')
            ->orderBy('products.name')
            ->get();

        $totals = [
            'sku_count' => $products->count(),
            'total_units' => (int) $products->sum('stock'),
            'total_value' => (int) $products->sum(fn ($p) => $p->stock * $p->price),
            'out_count' => $products->where('stock', '<=', 0)->count(),
            'low_count' => $products->whereBetween('stock', [1, 4])->count(),
        ];

        return view('pages.reports.inventory', [
            'products' => $products,
            'totals' => $totals,
            'categoryList' => Category::orderBy('name')->get(['id', 'name']),
            'stockStatus' => $stockStatus,
            'categoryId' => $categoryId,
        ]);
    }

    public function export(string $type, Request $request)
    {
        $range = DateRangeResolver::fromRequest($request);
        $format = $request->get('format', 'xlsx');

        $filters = [
            'from' => $range->from->toDateTimeString(),
            'to' => $range->to->toDateTimeString(),
            'kasir_id' => $request->kasir_id,
            'payment_method' => $request->payment_method,
            'category_id' => $request->category_id,
            'status' => $request->status,
            'stock_status' => $request->stock_status,
        ];

        return (new ReportExporter($type, $filters))->download($format);
    }

    private function applyOrderFilters($query, Request $request)
    {
        return $query
            ->when($request->filled('kasir_id'), fn ($q) => $q->where('kasir_id', $request->kasir_id))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
    }
}
