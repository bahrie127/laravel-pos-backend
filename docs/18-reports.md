# Step 18 — Reports (7 jenis + Export Multi-format)

## Tujuan

Halaman Laporan lengkap untuk admin/owner:
1. **Summary** — total revenue, orders, items, avg per order, breakdown per kasir.
2. **Product Sales** — top produk by qty & revenue + chart.
3. **Close Cashier** — settlement per kasir + payment breakdown.
4. **Promo Usage** — pemakaian promo + total diskon.
5. **Sales Analytics** — peak hour, day-of-week, top categories.
6. **Inventory** — stock value, low stock, last sold.
7. **Hub page** — entry point yang list semua report.

Export semua report ke **xlsx, csv, pdf, print**.

## Prasyarat

- Step 17 selesai

## Konteks

Reports = killer feature untuk owner. Data sudah ada di DB, kita aggregate + present. Filter date range konsisten di semua report pakai `<x-reports-filter-bar>`.

## Prompt untuk AI

````
Project Laravel POS sudah punya Promos. Sekarang buat 7 jenis Report di web.

A. ROUTE

1. `routes/web.php`:
```php
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
    Route::get('/summary', [\App\Http\Controllers\ReportController::class, 'summary'])->name('summary');
    Route::get('/product-sales', [\App\Http\Controllers\ReportController::class, 'productSales'])->name('product-sales');
    Route::get('/close-cashier', [\App\Http\Controllers\ReportController::class, 'closeCashier'])->name('close-cashier');
    Route::get('/promo-usage', [\App\Http\Controllers\ReportController::class, 'promoUsage'])->name('promo-usage');
    Route::get('/sales-analytics', [\App\Http\Controllers\ReportController::class, 'salesAnalytics'])->name('sales-analytics');
    Route::get('/inventory', [\App\Http\Controllers\ReportController::class, 'inventory'])->name('inventory');
    Route::get('/export/{type}', [\App\Http\Controllers\ReportController::class, 'export'])->name('export');
});
```

B. CONTROLLER

2. `app/Http/Controllers/ReportController.php`:
```php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\Promo;
use App\Models\User;
use App\Exports\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ReportController extends Controller {
    public function __construct() {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()->can('view-reports'), 403);
            return $next($request);
        });
    }

    public function index() { return view('pages.reports.index'); }

    public function summary(Request $request) {
        [$from, $to] = $this->resolveRange($request);

        $base = Order::whereBetween('transaction_time', [$from, $to])
                     ->where('status', Order::STATUS_PAID);

        $stats = [
            'total_revenue' => (int) (clone $base)->sum('total_price'),
            'total_orders' => (clone $base)->count(),
            'total_items' => (int) (clone $base)->sum('total_item'),
            'avg_per_order' => (int) (clone $base)->avg('total_price'),
        ];

        $daily = (clone $base)->selectRaw('DATE(transaction_time) date, SUM(total_price) total, COUNT(*) count')
            ->groupBy('date')->orderBy('date')->get();

        $perKasir = (clone $base)->join('users','orders.kasir_id','=','users.id')
            ->selectRaw('users.name kasir_name, COUNT(*) order_count, SUM(total_price) revenue')
            ->groupBy('users.id','users.name')->orderByDesc('revenue')->get();

        $paymentBreakdown = (clone $base)
            ->selectRaw('payment_method, COUNT(*) count, SUM(total_price) total')
            ->groupBy('payment_method')->get();

        return view('pages.reports.summary', compact('stats','daily','perKasir','paymentBreakdown','from','to'));
    }

    public function productSales(Request $request) {
        [$from, $to] = $this->resolveRange($request);
        $rows = OrderItem::join('orders','order_items.order_id','=','orders.id')
            ->join('products','order_items.product_id','=','products.id')
            ->leftJoin('categories','products.category_id','=','categories.id')
            ->whereBetween('orders.transaction_time', [$from, $to])
            ->where('orders.status', Order::STATUS_PAID)
            ->selectRaw('products.id, products.name product_name, categories.name category_name,
                         SUM(order_items.quantity) qty_sold,
                         SUM(order_items.total_price) revenue')
            ->groupBy('products.id','products.name','categories.name')
            ->orderByDesc('revenue')->get();

        $totalRevenue = $rows->sum('revenue');
        return view('pages.reports.product-sales', compact('rows','totalRevenue','from','to'));
    }

    public function closeCashier(Request $request) {
        [$from, $to] = $this->resolveRange($request);
        $rows = Order::join('users','orders.kasir_id','=','users.id')
            ->whereBetween('transaction_time', [$from, $to])
            ->where('status', Order::STATUS_PAID)
            ->selectRaw('users.name kasir_name, payment_method,
                         COUNT(*) order_count, SUM(total_price) revenue')
            ->groupBy('users.id','users.name','payment_method')
            ->orderBy('users.name')->orderBy('payment_method')->get();
        return view('pages.reports.close-cashier', compact('rows','from','to'));
    }

    public function promoUsage(Request $request) {
        [$from, $to] = $this->resolveRange($request);
        $rows = Promo::leftJoin('orders','promos.id','=','orders.promo_id')
            ->whereBetween('orders.transaction_time', [$from, $to])
            ->orWhereNull('orders.id')
            ->selectRaw('promos.id, promos.name, promos.code, promos.type,
                         COUNT(orders.id) usage_count, SUM(orders.discount_amount) total_discount')
            ->groupBy('promos.id','promos.name','promos.code','promos.type')
            ->orderByDesc('usage_count')->get();
        return view('pages.reports.promo-usage', compact('rows','from','to'));
    }

    public function salesAnalytics(Request $request) {
        [$from, $to] = $this->resolveRange($request);
        $base = Order::whereBetween('transaction_time', [$from, $to])
                     ->where('status', Order::STATUS_PAID);

        $hourly = (clone $base)->selectRaw('HOUR(transaction_time) hour, COUNT(*) count, SUM(total_price) revenue')
            ->groupBy('hour')->orderBy('hour')->get();

        $dayOfWeek = (clone $base)->selectRaw('DAYOFWEEK(transaction_time) dow, COUNT(*) count, SUM(total_price) revenue')
            ->groupBy('dow')->orderBy('dow')->get();

        $peakHour = $hourly->sortByDesc('revenue')->first()?->hour;
        $bestDay = $dayOfWeek->sortByDesc('revenue')->first()?->dow;

        $topCategories = OrderItem::join('orders','order_items.order_id','=','orders.id')
            ->join('products','order_items.product_id','=','products.id')
            ->leftJoin('categories','products.category_id','=','categories.id')
            ->whereBetween('orders.transaction_time',[$from,$to])
            ->where('orders.status', Order::STATUS_PAID)
            ->selectRaw('COALESCE(categories.name, "Lainnya") cat, SUM(order_items.total_price) revenue')
            ->groupBy('cat')->orderByDesc('revenue')->limit(5)->get();

        return view('pages.reports.sales-analytics', compact('hourly','dayOfWeek','peakHour','bestDay','topCategories','from','to'));
    }

    public function inventory(Request $request) {
        $filter = $request->stock_filter;
        $query = Product::leftJoin('order_items','order_items.product_id','=','products.id')
            ->leftJoin('orders','order_items.order_id','=','orders.id')
            ->leftJoin('categories','products.category_id','=','categories.id')
            ->selectRaw('products.id, products.name, products.price, products.stock,
                         categories.name category_name,
                         COALESCE(SUM(order_items.quantity),0) sold,
                         MAX(orders.transaction_time) last_sold')
            ->groupBy('products.id','products.name','products.price','products.stock','categories.name');

        if ($filter === 'out') $query->having('products.stock', '=', 0);
        if ($filter === 'low') $query->havingRaw('products.stock BETWEEN 1 AND 4');

        $rows = $query->orderBy('products.stock')->get();
        $totalValue = $rows->sum(fn($r) => $r->stock * $r->price);
        return view('pages.reports.inventory', compact('rows','totalValue','filter'));
    }

    public function export(string $type, Request $request) {
        $format = $request->get('format', 'xlsx'); // xlsx|csv|pdf
        [$from, $to] = $this->resolveRange($request);
        $exporter = new ReportExport($type, $request->all(), $from, $to);

        return match($format) {
            'csv' => Excel::download($exporter, "report-{$type}-".now()->format('Ymd').'.csv', \Maatwebsite\Excel\Excel::CSV),
            'pdf' => $exporter->toPdf(),
            default => Excel::download($exporter, "report-{$type}-".now()->format('Ymd').'.xlsx'),
        };
    }

    private function resolveRange(Request $request): array {
        $preset = $request->get('preset', 'today');
        $from = $request->get('from') ? Carbon::parse($request->get('from'))->startOfDay() : null;
        $to = $request->get('to') ? Carbon::parse($request->get('to'))->endOfDay() : null;

        if (! $from || ! $to) {
            [$from, $to] = match($preset) {
                'today' => [today(), now()],
                'week' => [now()->startOfWeek(), now()],
                'month' => [now()->startOfMonth(), now()],
                'year' => [now()->startOfYear(), now()],
                default => [today(), now()],
            };
        }
        return [$from, $to];
    }
}
```

C. EXPORTER

3. `app/Exports/ReportExport.php`:
```php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithTitle};
use Barryvdh\DomPDF\Facade\Pdf;

class ReportExport implements FromCollection, WithHeadings, WithTitle {
    public function __construct(
        protected string $type,
        protected array $filters,
        protected $from, protected $to,
    ) {}

    public function collection() {
        // Build rows sesuai $this->type. Logic mirror controller.
        return collect($this->buildData()['rows']);
    }

    public function headings(): array {
        return $this->buildData()['headings'];
    }

    public function title(): string {
        return ucfirst($this->type);
    }

    public function toPdf() {
        $data = $this->buildData();
        return Pdf::loadView('pages.reports.pdf', [
            'title' => $this->type, 'rows' => $data['rows'], 'headings' => $data['headings'],
            'from' => $this->from, 'to' => $this->to,
        ])->setPaper('A4', 'landscape')->download("report-{$this->type}.pdf");
    }

    private function buildData(): array {
        // Sederhana, satu method per type. Lebih clean kalau di-refactor jadi strategy class.
        return match($this->type) {
            'summary' => [
                'headings' => ['Tanggal','Total Order','Revenue'],
                'rows' => \App\Models\Order::whereBetween('transaction_time',[$this->from,$this->to])
                    ->where('status', \App\Models\Order::STATUS_PAID)
                    ->selectRaw('DATE(transaction_time) tgl, COUNT(*) total, SUM(total_price) rev')
                    ->groupBy('tgl')->get()
                    ->map(fn($r) => [$r->tgl, $r->total, $r->rev])->toArray(),
            ],
            // ... 6 type lainnya
            default => ['headings' => [], 'rows' => []],
        };
    }
}
```

D. VIEW

4. `pages/reports/index.blade.php` (hub):
```blade
@extends('layouts.app')
@section('title', __('Laporan'))
@section('main')
<section class="section">
    <x-page-header title="{{ __('Laporan') }}"/>
    <div class="row">
        @foreach([
            ['icon'=>'chart-bar','color'=>'primary','title'=>'Ringkasan','desc'=>'Total revenue, orders, items, kasir.','route'=>'reports.summary'],
            ['icon'=>'cube','color'=>'success','title'=>'Penjualan Produk','desc'=>'Top produk by qty & revenue.','route'=>'reports.product-sales'],
            ['icon'=>'cash-register','color'=>'warning','title'=>'Tutup Kasir','desc'=>'Settlement per kasir.','route'=>'reports.close-cashier'],
            ['icon'=>'percent','color'=>'info','title'=>'Pemakaian Promo','desc'=>'Promo terpopuler & total diskon.','route'=>'reports.promo-usage'],
            ['icon'=>'chart-line','color'=>'primary','title'=>'Sales Analytics','desc'=>'Peak hour, day-of-week, top kategori.','route'=>'reports.sales-analytics'],
            ['icon'=>'boxes','color'=>'danger','title'=>'Stok Barang','desc'=>'Stock value, low stock, last sold.','route'=>'reports.inventory'],
        ] as $r)
        <div class="col-md-4 mb-3">
            <a href="{{ route($r['route']) }}" class="card-clean d-block text-decoration-none text-dark h-100">
                <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-3" style="width:48px;height:48px;background:var(--primary-50);color:var(--primary-600)">
                    <i class="fas fa-{{ $r['icon'] }} fa-lg"></i>
                </div>
                <h5>{{ __($r['title']) }}</h5>
                <p class="text-muted mb-0">{{ __($r['desc']) }}</p>
            </a>
        </div>
        @endforeach
    </div>
</section>
@endsection
```

5. `pages/reports/summary.blade.php` (contoh, 6 lainnya pakai pola sama):
```blade
@extends('layouts.app')
@section('title', __('Ringkasan Penjualan'))
@section('main')
<section class="section">
    <x-page-header title="{{ __('Ringkasan Penjualan') }}"
        subtitle="{{ formatDate($from,'d M Y') }} – {{ formatDate($to,'d M Y') }}"
        :breadcrumbs="[['label'=>__('Laporan'),'url'=>route('reports.index')], ['label'=>__('Ringkasan')]]">
        <x-slot:actions>
            <a href="{{ route('reports.export', ['type'=>'summary','format'=>'xlsx'] + request()->query()) }}" class="btn btn-outline-success"><i class="fas fa-file-excel me-2"></i>Excel</a>
            <a href="{{ route('reports.export', ['type'=>'summary','format'=>'pdf'] + request()->query()) }}" class="btn btn-outline-danger"><i class="fas fa-file-pdf me-2"></i>PDF</a>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-2"></i>{{ __('Cetak') }}</button>
        </x-slot:actions>
    </x-page-header>

    <x-reports-filter action="{{ route('reports.summary') }}" :from="$from" :to="$to"/>

    <div class="row mb-3">
        <div class="col-md-3"><x-stat-card label="{{ __('Total Pendapatan') }}" value="{{ rupiah($stats['total_revenue']) }}" icon="money-bill-wave" color="success"/></div>
        <div class="col-md-3"><x-stat-card label="{{ __('Total Transaksi') }}" value="{{ $stats['total_orders'] }}" icon="receipt" color="primary"/></div>
        <div class="col-md-3"><x-stat-card label="{{ __('Item Terjual') }}" value="{{ $stats['total_items'] }}" icon="cube" color="warning"/></div>
        <div class="col-md-3"><x-stat-card label="{{ __('Rata-rata / Trx') }}" value="{{ rupiah($stats['avg_per_order']) }}" icon="chart-bar" color="info"/></div>
    </div>

    <div class="card-clean mb-3">
        <h5 class="mb-3">{{ __('Pendapatan Harian') }}</h5>
        <canvas id="dailyChart" height="80"></canvas>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card-clean">
                <h5 class="mb-3">{{ __('Performa Kasir') }}</h5>
                <table class="table">
                    <thead><tr><th>{{ __('Kasir') }}</th><th class="text-end">{{ __('Trx') }}</th><th class="text-end">{{ __('Revenue') }}</th></tr></thead>
                    <tbody>
                        @foreach($perKasir as $k)
                            <tr><td>{{ $k->kasir_name }}</td><td class="text-end">{{ $k->order_count }}</td><td class="text-end fw-medium">{{ rupiah($k->revenue) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-clean">
                <h5 class="mb-3">{{ __('Breakdown Pembayaran') }}</h5>
                <canvas id="paymentChart" height="200"></canvas>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script src="{{ asset('library/chart.js/Chart.min.js') }}"></script>
<script>
const daily = @json($daily);
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: daily.map(r => r.date),
        datasets: [{ label: 'Revenue', data: daily.map(r => r.total), borderColor:'#2563EB', tension:0.3 }]
    }
});
const pb = @json($paymentBreakdown);
new Chart(document.getElementById('paymentChart'), {
    type: 'doughnut',
    data: {
        labels: pb.map(r => r.payment_method.toUpperCase()),
        datasets: [{ data: pb.map(r => r.total), backgroundColor:['#3B82F6','#10B981','#F59E0B','#EC4899'] }]
    }
});
</script>
@endpush
@endsection
```

6. Buat 5 view lainnya dengan pola sama:
- `product-sales.blade.php` — table ranked + bar chart top 10.
- `close-cashier.blade.php` — pivot table kasir x payment method.
- `promo-usage.blade.php` — table promo + total diskon.
- `sales-analytics.blade.php` — chart hourly distribution + best day card.
- `inventory.blade.php` — table stock dengan low-stock alert + total stock value.

7. `pages/reports/pdf.blade.php` (template umum untuk PDF):
```blade
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:sans-serif;font-size:11px}table{width:100%;border-collapse:collapse}th,td{padding:6px;border-bottom:1px solid #ddd}</style>
</head><body>
<h2>Laporan {{ ucfirst($title) }}</h2>
<p>{{ formatDate($from,'d M Y') }} - {{ formatDate($to,'d M Y') }}</p>
<table>
    <thead><tr>@foreach($headings as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
    <tbody>
        @foreach($rows as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach
    </tbody>
</table>
</body></html>
```

Tampilkan struktur folder `pages/reports/` setelah selesai.
````

## Hasil yang Diharapkan

```
app/
├── Http/Controllers/ReportController.php
└── Exports/ReportExport.php
resources/views/pages/reports/
├── index.blade.php
├── summary.blade.php
├── product-sales.blade.php
├── close-cashier.blade.php
├── promo-usage.blade.php
├── sales-analytics.blade.php
├── inventory.blade.php
└── pdf.blade.php
```

## Cara Test

```bash
# 1. Login owner → /reports → 6 card report
# 2. Klik "Ringkasan" → /reports/summary?preset=today
# 3. Stat cards berisi data, chart muncul, table kasir performance
# 4. Klik preset "Bulan Ini" → URL update, data refresh
# 5. Export Excel → download xlsx
# 6. Export PDF → download PDF landscape
# 7. Print → browser print preview

# Test report lain (product-sales, close-cashier, dll) → ekspektasi sama
```

## Penjelasan untuk Murid

Talking points:

1. **"`resolveRange()` private helper?"** — DRY. Semua 6 report butuh date range parsing. Method privat hindari copy-paste.

2. **"`clone $base` untuk reuse query?"** — Karena setelah `count()`/`sum()`, query builder belum di-execute. Tapi method modifier (where) langsung mutate. Clone supaya tidak terkontaminasi.

3. **"`selectRaw` vs `select` + DB::raw?"** — Sama. `selectRaw` lebih ringkas untuk SQL expression.

4. **"`having` vs `where`?"** — `having` filter HASIL aggregate (post-groupBy). `where` filter ROW (pre-groupBy). Inventory query pakai `having` untuk filter berdasarkan SUM result.

5. **"Middleware check `view-reports` di constructor?"** — Auto-protect semua method. Tidak perlu `authorize` di setiap method.

6. **"Chart.js render dari `@json($data)`?"** — Pass PHP array ke JS via `@json`. Auto-escape, safe untuk inject di script tag.

7. **"Export 1 controller method untuk 7 type — pattern match?"** — Match expression cleaner dari if-else. Kalau type list growing, refactor ke strategy/factory pattern.

Pertanyaan reflektif:
- "Kalau report query lambat (data 1 tahun = 500k row), optimasi?" (→ pakai `chunk` + index date column, atau materialized view + cron rebuild nightly)
- "Bagaimana support multi-currency di summary report?" (→ tambah `currency` column di orders, group by currency, format pakai locale-specific)

---

**Next: [19-api-mobile.md](19-api-mobile.md)**
