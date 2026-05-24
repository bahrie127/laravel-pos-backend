<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
        [$from, $to] = $this->parseDateRange($request);

        $query = Order::query()
            ->whereBetween('transaction_time', [$from, $to])
            ->when($request->filled('kasir_id'), fn ($q) => $q->where('kasir_id', $request->kasir_id));

        $summary = [
            'total_revenue' => (float) (clone $query)->sum('total_price'),
            'total_orders' => (clone $query)->count(),
            'total_items' => (int) (clone $query)->sum('total_item'),
            'avg_per_order' => (float) (clone $query)->avg('total_price'),
        ];

        $dailyRevenue = (clone $query)
            ->selectRaw('DATE(transaction_time) as date, SUM(total_price) as total, COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $paymentBreakdown = (clone $query)
            ->whereNotNull('payment_method')
            ->selectRaw('payment_method, SUM(total_price) as total, COUNT(*) as orders')
            ->groupBy('payment_method')
            ->get();

        $kasirPerformance = (clone $query)
            ->join('users', 'orders.kasir_id', '=', 'users.id')
            ->selectRaw('users.name as kasir_name, COUNT(*) as order_count, SUM(orders.total_price) as total_revenue')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_revenue')
            ->get();

        $kasirList = User::orderBy('name')->get(['id', 'name']);

        return view('pages.reports.summary', compact(
            'summary', 'dailyRevenue', 'paymentBreakdown', 'kasirPerformance',
            'from', 'to', 'kasirList'
        ));
    }

    public function productSales(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);

        $items = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.transaction_time', [$from, $to])
            ->selectRaw('
                products.id,
                products.name,
                products.category,
                SUM(order_items.quantity) as qty_sold,
                SUM(order_items.total_price) as revenue
            ')
            ->groupBy('products.id', 'products.name', 'products.category')
            ->orderByDesc('revenue')
            ->get();

        $totalRevenue = (float) $items->sum('revenue');
        $items = $items->map(function ($row) use ($totalRevenue) {
            $row->percentage = $totalRevenue > 0 ? round(($row->revenue / $totalRevenue) * 100, 1) : 0;

            return $row;
        });

        return view('pages.reports.product-sales', compact('items', 'totalRevenue', 'from', 'to'));
    }

    public function closeCashier(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);

        $kasirId = $request->kasir_id;
        $kasirList = User::orderBy('name')->get(['id', 'name']);

        $rows = collect();
        $totals = ['count' => 0, 'revenue' => 0];

        if ($kasirId) {
            $query = Order::query()
                ->where('kasir_id', $kasirId)
                ->whereBetween('transaction_time', [$from, $to]);

            $rows = (clone $query)
                ->selectRaw('payment_method, COUNT(*) as orders, SUM(total_price) as total')
                ->groupBy('payment_method')
                ->get();

            $totals['count'] = (clone $query)->count();
            $totals['revenue'] = (float) (clone $query)->sum('total_price');
        }

        $selectedKasir = $kasirId ? User::find($kasirId) : null;

        return view('pages.reports.close-cashier', compact(
            'rows', 'totals', 'kasirList', 'selectedKasir', 'from', 'to'
        ));
    }

    public function export(string $type, Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);

        $filename = "report-{$type}-" . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new ReportExport($type, [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'kasir_id' => $request->kasir_id,
        ]), $filename);
    }

    private function parseDateRange(Request $request): array
    {
        $from = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfMonth();

        $to = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }
}
