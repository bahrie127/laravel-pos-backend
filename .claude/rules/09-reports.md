# Reports Section (BARU di Web)

## Current State

Endpoint API sudah ada di `routes/api.php`:
- `GET /api/reports/summary` → ringkasan transaksi
- `GET /api/reports/product-sales` → penjualan per produk
- `GET /api/reports/close-cashier` → tutup kasir

`ReportController.php` ada di `app/Http/Controllers/Api/`.

**Tapi tidak ada halaman web** untuk akses reports ini. Admin tidak punya cara lihat report kecuali via Flutter app atau hit API mentah.

## Target Enhancement

Buat **3 halaman report di web** + 1 hub page yang me-list semua report.

### Route additions

```php
// routes/web.php
Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/summary', [ReportController::class, 'summary'])->name('summary');
    Route::get('/product-sales', [ReportController::class, 'productSales'])->name('product-sales');
    Route::get('/close-cashier', [ReportController::class, 'closeCashier'])->name('close-cashier');
    Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
});
```

### Reports hub (`pages/reports/index.blade.php`)

```blade
@extends('layouts.app')
@section('title', __('Laporan'))

@section('main')
<div class="space-y-4">
    <h1 class="text-2xl font-bold">{{ __('Laporan') }}</h1>
    <p class="text-gray-500">{{ __('Pilih laporan untuk dilihat atau diekspor.') }}</p>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('reports.summary') }}" class="card hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center mb-3">
                <x-icon name="chart-bar" class="w-6 h-6"/>
            </div>
            <h3 class="font-semibold group-hover:text-primary-600">{{ __('Ringkasan Penjualan') }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ __('Total revenue, jumlah transaksi, rata-rata per hari') }}</p>
        </a>
        
        <a href="{{ route('reports.product-sales') }}" class="card hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-lg bg-success-100 text-success-600 flex items-center justify-center mb-3">
                <x-icon name="cube" class="w-6 h-6"/>
            </div>
            <h3 class="font-semibold group-hover:text-primary-600">{{ __('Penjualan per Produk') }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ __('Produk terlaris, omset per produk, qty terjual') }}</p>
        </a>
        
        <a href="{{ route('reports.close-cashier') }}" class="card hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-lg bg-warning-100 text-warning-600 flex items-center justify-center mb-3">
                <x-icon name="banknotes" class="w-6 h-6"/>
            </div>
            <h3 class="font-semibold group-hover:text-primary-600">{{ __('Tutup Kasir') }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ __('Setoran kasir per shift, breakdown payment method') }}</p>
        </a>
    </div>
</div>
@endsection
```

### Summary report (`pages/reports/summary.blade.php`)

```blade
@extends('layouts.app')
@section('title', __('Ringkasan Penjualan'))

@section('main')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-bold">{{ __('Ringkasan Penjualan') }}</h1>
            <p class="text-sm text-gray-500">
                {{ __('Periode: :from - :to', ['from' => $from->translatedFormat('d M Y'), 'to' => $to->translatedFormat('d M Y')]) }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reports.export', 'summary') }}?{{ http_build_query(request()->all()) }}" class="btn btn-secondary">
                <x-icon name="arrow-down-tray" class="w-4 h-4 mr-2"/> {{ __('Export Excel') }}
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <x-icon name="printer" class="w-4 h-4 mr-2"/> {{ __('Print') }}
            </button>
        </div>
    </div>
    
    {{-- Filter --}}
    <div class="card">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="date_range" value="{{ request('date_range') }}" class="form-input daterange-picker" placeholder="{{ __('Pilih periode') }}">
            <select name="kasir_id" class="form-select">
                <option value="">{{ __('Semua Kasir') }}</option>
                @foreach ($kasirList as $k)
                    <option value="{{ $k->id }}" @selected(request('kasir_id') == $k->id)>{{ $k->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">{{ __('Tampilkan') }}</button>
        </form>
    </div>
    
    {{-- Stat cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-stat-card label="{{ __('Total Pendapatan') }}" value="Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}" icon="banknotes" color="success"/>
        <x-stat-card label="{{ __('Total Transaksi') }}" value="{{ $summary['total_orders'] }}" icon="receipt" color="primary"/>
        <x-stat-card label="{{ __('Item Terjual') }}" value="{{ $summary['total_items'] }}" icon="cube" color="warning"/>
        <x-stat-card label="{{ __('Rata-rata / Transaksi') }}" value="Rp {{ number_format($summary['avg_per_order'], 0, ',', '.') }}" icon="chart-bar" color="info"/>
    </div>
    
    {{-- Chart --}}
    <div class="card">
        <h3 class="font-semibold mb-4">{{ __('Pendapatan per Hari') }}</h3>
        <canvas id="dailyRevenueChart" height="100"></canvas>
    </div>
    
    {{-- Breakdown by payment method --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <h3 class="font-semibold mb-4">{{ __('Breakdown Pembayaran') }}</h3>
            <canvas id="paymentChart" height="200"></canvas>
        </div>
        
        <div class="card">
            <h3 class="font-semibold mb-4">{{ __('Performa Kasir') }}</h3>
            <table class="w-full text-sm">
                <thead class="text-xs uppercase text-gray-500 border-b">
                    <tr>
                        <th class="py-2 text-left">{{ __('Kasir') }}</th>
                        <th class="text-right">{{ __('Transaksi') }}</th>
                        <th class="text-right">{{ __('Pendapatan') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($kasirPerformance as $row)
                        <tr class="border-b last:border-0">
                            <td class="py-3">{{ $row->kasir_name }}</td>
                            <td class="text-right">{{ $row->order_count }}</td>
                            <td class="text-right font-medium">Rp {{ number_format($row->total_revenue, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
```

### Product sales report

Layout: filter periode + tabel sortable + chart top-10 product.

Kolom: Rank, Product Name, Category, Qty Sold, Revenue, % of Total.

### Close cashier report

Layout: per-shift breakdown. Filter: tanggal + kasir.

```
┌────────────────────────────────────────────┐
│ Kasir: John Doe                            │
│ Shift: 24 Mei 2026, 08:00 - 17:00          │
├────────────────────────────────────────────┤
│ Cash:          Rp 1.250.000   (15 trx)    │
│ QRIS:          Rp 850.000     (10 trx)    │
│ Transfer:      Rp 320.000     (3 trx)     │
├────────────────────────────────────────────┤
│ Total:         Rp 2.420.000   (28 trx)    │
│                                            │
│ Setoran fisik (cash): _____________  [Input]│
│ Selisih:       _________________            │
└────────────────────────────────────────────┘
```

### Controller (web)

```php
// app/Http/Controllers/ReportController.php (web)
class ReportController extends Controller
{
    public function index() { return view('pages.reports.index'); }
    
    public function summary(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request->date_range);
        
        $query = Order::whereBetween('transaction_time', [$from, $to]);
        if ($request->filled('kasir_id')) {
            $query->where('kasir_id', $request->kasir_id);
        }
        
        $summary = [
            'total_revenue' => $query->sum('total_price'),
            'total_orders' => $query->count(),
            'total_items' => $query->sum('total_item'),
            'avg_per_order' => $query->avg('total_price') ?? 0,
        ];
        
        $dailyRevenue = $query->selectRaw('DATE(transaction_time) date, SUM(total_price) total')
            ->groupBy('date')->orderBy('date')->get();
        
        $paymentBreakdown = $query->selectRaw('payment_method, SUM(total_price) total')
            ->groupBy('payment_method')->get();
        
        $kasirPerformance = $query->join('users', 'orders.kasir_id', '=', 'users.id')
            ->selectRaw('users.name as kasir_name, COUNT(*) as order_count, SUM(total_price) as total_revenue')
            ->groupBy('users.id', 'users.name')->orderByDesc('total_revenue')->get();
        
        $kasirList = User::where('roles', 'kasir')->get(['id', 'name']);
        
        return view('pages.reports.summary', compact('summary', 'dailyRevenue', 'paymentBreakdown', 'kasirPerformance', 'from', 'to', 'kasirList'));
    }
    
    public function productSales(Request $request) { /* ... */ }
    public function closeCashier(Request $request) { /* ... */ }
    
    public function export(string $type, Request $request)
    {
        $exporter = match ($type) {
            'summary' => new SummaryReportExport($request->all()),
            'product-sales' => new ProductSalesReportExport($request->all()),
            'close-cashier' => new CloseCashierReportExport($request->all()),
        };
        
        return Excel::download($exporter, "report-{$type}-" . now()->format('Ymd') . '.xlsx');
    }
    
    private function parseDateRange(?string $range): array
    {
        if (!$range) {
            return [now()->startOfMonth(), now()->endOfDay()];
        }
        [$from, $to] = explode(' - ', $range);
        return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
    }
}
```

### Excel Exporters

Pakai `maatwebsite/excel`:

```bash
composer require maatwebsite/excel
```

```php
// app/Exports/SummaryReportExport.php
class SummaryReportExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private array $filters) {}
    
    public function array(): array { /* build rows */ }
    public function headings(): array { return ['Tanggal', 'Total Trx', 'Revenue', 'Items']; }
    public function styles(Worksheet $sheet) { /* bold header */ }
}
```

### PDF version (opsional)

```php
// pakai dompdf
public function summaryPdf(Request $request)
{
    $data = $this->buildSummaryData($request);
    $pdf = Pdf::loadView('pages.reports.summary-pdf', $data);
    return $pdf->download('summary-' . now()->format('Ymd') . '.pdf');
}
```

## Action Items

- [ ] Route group `reports.*` di `routes/web.php`
- [ ] Buat `ReportController` di `app/Http/Controllers/` (web, terpisah dari API)
- [ ] Buat view: `pages/reports/index.blade.php`, `summary.blade.php`, `product-sales.blade.php`, `close-cashier.blade.php`
- [ ] Date range picker component (`<x-date-range-picker>` pakai literally-daterangepicker)
- [ ] Install `maatwebsite/excel` untuk export
- [ ] Buat 3 export class: SummaryReportExport, ProductSalesReportExport, CloseCashierReportExport
- [ ] Tambah menu "Laporan" di sidebar (sudah di-cover di `02-layout-sidebar.md`)
- [ ] Print-friendly CSS (@media print) di setiap report page
- [ ] Akses control: hanya owner & admin yang boleh akses reports (policy)
- [ ] Cache report query yang berat (Phase 3) — pakai Redis cache 5-15 menit
