<?php

namespace App\Reports;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Centralized builder for tabular report data. The same rows feed:
 *   - on-screen tables
 *   - Excel/CSV/PDF exporters
 *
 * Each method returns ['title', 'headings', 'rows', 'summary' (optional)].
 *
 * Filters supported (per type):
 *   summary:        kasir_id, payment_method, status
 *   product_sales:  category_id
 *   close_cashier:  kasir_id (required)
 *   promo_usage:    -
 *   sales_analytics: kasir_id, payment_method
 *   inventory:      category_id, stock_status (low|out|in)
 */
class ReportDataBuilder
{
    public function __construct(
        private Carbon $from,
        private Carbon $to,
        private array $filters = []
    ) {}

    public function build(string $type): array
    {
        return match ($type) {
            'summary' => $this->summary(),
            'product-sales' => $this->productSales(),
            'close-cashier' => $this->closeCashier(),
            'promo-usage' => $this->promoUsage(),
            'sales-analytics' => $this->salesAnalytics(),
            'inventory' => $this->inventory(),
            default => ['title' => 'Laporan', 'headings' => [], 'rows' => collect()],
        };
    }

    private function baseOrderQuery()
    {
        return Order::query()
            ->whereBetween('transaction_time', [$this->from, $this->to])
            ->when(! empty($this->filters['kasir_id']), fn ($q) => $q->where('kasir_id', $this->filters['kasir_id']))
            ->when(! empty($this->filters['payment_method']), fn ($q) => $q->where('payment_method', $this->filters['payment_method']))
            ->when(! empty($this->filters['status']), fn ($q) => $q->where('status', $this->filters['status']));
    }

    private function summary(): array
    {
        $rows = $this->baseOrderQuery()
            ->selectRaw('DATE(transaction_time) as date, COUNT(*) as orders, SUM(total_item) as items, SUM(total_price) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'title' => 'Ringkasan Penjualan',
            'headings' => ['Tanggal', 'Jumlah Order', 'Item Terjual', 'Total Pendapatan'],
            'rows' => $rows->map(fn ($r) => [
                $r->date,
                (int) $r->orders,
                (int) $r->items,
                (int) $r->total,
            ]),
            'summary' => [
                'Total Order' => $rows->sum('orders'),
                'Total Item' => $rows->sum('items'),
                'Total Pendapatan' => (int) $rows->sum('total'),
            ],
        ];
    }

    private function productSales(): array
    {
        $items = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('orders.transaction_time', [$this->from, $this->to])
            ->when(! empty($this->filters['kasir_id']), fn ($q) => $q->where('orders.kasir_id', $this->filters['kasir_id']))
            ->when(! empty($this->filters['category_id']), fn ($q) => $q->where('products.category_id', $this->filters['category_id']))
            ->selectRaw('
                products.name,
                COALESCE(categories.name, products.category, "—") as cat,
                SUM(order_items.quantity) as qty,
                SUM(order_items.total_price) as revenue
            ')
            ->groupBy('products.id', 'products.name', 'cat')
            ->orderByDesc('revenue')
            ->get();

        $total = (float) $items->sum('revenue');

        return [
            'title' => 'Penjualan per Produk',
            'headings' => ['Rank', 'Produk', 'Kategori', 'Qty Terjual', 'Pendapatan', '% Kontribusi'],
            'rows' => $items->values()->map(fn ($r, $i) => [
                $i + 1,
                $r->name,
                $r->cat,
                (int) $r->qty,
                (int) $r->revenue,
                $total > 0 ? round(($r->revenue / $total) * 100, 1) . '%' : '0%',
            ]),
            'summary' => [
                'Jumlah Produk' => $items->count(),
                'Total Qty' => (int) $items->sum('qty'),
                'Total Pendapatan' => (int) $items->sum('revenue'),
            ],
        ];
    }

    private function closeCashier(): array
    {
        $kasirId = $this->filters['kasir_id'] ?? null;

        if (! $kasirId) {
            return [
                'title' => 'Tutup Kasir',
                'headings' => ['Pembayaran', 'Jumlah Order', 'Total'],
                'rows' => collect(),
                'summary' => [],
            ];
        }

        $rows = Order::where('kasir_id', $kasirId)
            ->whereBetween('transaction_time', [$this->from, $this->to])
            ->selectRaw('payment_method, COUNT(*) as orders, SUM(total_price) as total')
            ->groupBy('payment_method')
            ->get();

        return [
            'title' => 'Tutup Kasir',
            'headings' => ['Pembayaran', 'Jumlah Order', 'Total'],
            'rows' => $rows->map(fn ($r) => [
                $r->payment_method ?? '—',
                (int) $r->orders,
                (int) $r->total,
            ]),
            'summary' => [
                'Total Order' => (int) $rows->sum('orders'),
                'Total Pendapatan' => (int) $rows->sum('total'),
            ],
        ];
    }

    private function promoUsage(): array
    {
        $rows = Order::query()
            ->join('promos', 'orders.promo_id', '=', 'promos.id')
            ->whereBetween('orders.transaction_time', [$this->from, $this->to])
            ->selectRaw('
                promos.name,
                promos.code,
                promos.type,
                COUNT(*) as usage_count,
                SUM(orders.discount_amount) as total_discount,
                SUM(orders.total_price) as net_revenue
            ')
            ->groupBy('promos.id', 'promos.name', 'promos.code', 'promos.type')
            ->orderByDesc('total_discount')
            ->get();

        return [
            'title' => 'Pemakaian Promo',
            'headings' => ['Promo', 'Kode', 'Tipe', 'Dipakai', 'Total Diskon', 'Net Revenue'],
            'rows' => $rows->map(fn ($r) => [
                $r->name,
                $r->code ?? '—',
                $r->type,
                (int) $r->usage_count,
                (int) $r->total_discount,
                (int) $r->net_revenue,
            ]),
            'summary' => [
                'Promo Aktif Dipakai' => $rows->count(),
                'Total Diskon' => (int) $rows->sum('total_discount'),
                'Net Revenue' => (int) $rows->sum('net_revenue'),
            ],
        ];
    }

    private function salesAnalytics(): array
    {
        $rows = $this->baseOrderQuery()
            ->selectRaw('HOUR(transaction_time) as hour, COUNT(*) as orders, SUM(total_price) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'title' => 'Sales Analytics — Per Jam',
            'headings' => ['Jam', 'Jumlah Order', 'Total Pendapatan'],
            'rows' => $rows->map(fn ($r) => [
                sprintf('%02d:00', $r->hour),
                (int) $r->orders,
                (int) $r->total,
            ]),
            'summary' => [
                'Total Order' => (int) $rows->sum('orders'),
                'Total Pendapatan' => (int) $rows->sum('total'),
            ],
        ];
    }

    private function inventory(): array
    {
        $stockStatus = $this->filters['stock_status'] ?? null;
        $categoryId = $this->filters['category_id'] ?? null;

        $products = Product::query()
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'products.id', 'products.name', 'products.stock', 'products.price',
                DB::raw('COALESCE(categories.name, products.category, "—") as cat')
            )
            ->when($categoryId, fn ($q) => $q->where('products.category_id', $categoryId))
            ->when($stockStatus === 'out', fn ($q) => $q->where('products.stock', '<=', 0))
            ->when($stockStatus === 'low', fn ($q) => $q->whereBetween('products.stock', [1, 4]))
            ->when($stockStatus === 'in', fn ($q) => $q->where('products.stock', '>=', 5))
            ->orderBy('products.stock')
            ->orderBy('products.name')
            ->get();

        return [
            'title' => 'Laporan Stok',
            'headings' => ['Produk', 'Kategori', 'Stok', 'Harga', 'Nilai Stok', 'Status'],
            'rows' => $products->map(function ($p) {
                $status = $p->stock <= 0 ? 'Habis' : ($p->stock < 5 ? 'Menipis' : 'Aman');
                return [
                    $p->name,
                    $p->cat,
                    (int) $p->stock,
                    (int) $p->price,
                    (int) ($p->stock * $p->price),
                    $status,
                ];
            }),
            'summary' => [
                'Jumlah SKU' => $products->count(),
                'Total Unit' => (int) $products->sum('stock'),
                'Total Nilai Stok' => (int) $products->sum(fn ($p) => $p->stock * $p->price),
                'Stok Habis' => $products->where('stock', '<=', 0)->count(),
                'Stok Menipis' => $products->whereBetween('stock', [1, 4])->count(),
            ],
        ];
    }
}
