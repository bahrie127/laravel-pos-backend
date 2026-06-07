# Step 10 — Dashboard Real-Time

## Tujuan

Mengganti dashboard placeholder dengan **dashboard real berbasis data DB**:
- 4 stat card (revenue today, orders today, total products, kasir aktif).
- Quick stats baris kedua (revenue minggu, bulan, avg per order).
- Top 5 produk terlaris.
- 8 pesanan terbaru.
- 10 produk low stock.
- Chart breakdown pembayaran hari ini (Chart.js doughnut).
- Delta percentage vs kemarin.

## Prasyarat

- Step 09 selesai (policy + data)
- Step 08 seed sudah running (ada order historis untuk demo)

## Konteks

Sebelumnya `route('home')` cuma return view placeholder. Sekarang: controller real, query agregat, view dengan stat card + chart.

## Prompt untuk AI

````
Project Laravel POS sudah punya semua model, seeder, policy. Sekarang implement dashboard real.

A. CONTROLLER

1. Buat `app/Http/Controllers/DashboardController.php`:
```php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller {
    public function index() {
        $today = today();

        // Revenue & orders today
        $revenueToday = (int) Order::whereDate('transaction_time', $today)
            ->where('status', Order::STATUS_PAID)->sum('total_price');
        $ordersToday = Order::whereDate('transaction_time', $today)
            ->where('status', Order::STATUS_PAID)->count();

        // Revenue kemarin untuk delta
        $revenueYesterday = (int) Order::whereDate('transaction_time', $today->copy()->subDay())
            ->where('status', Order::STATUS_PAID)->sum('total_price');
        $revenueDelta = $revenueYesterday > 0
            ? round((($revenueToday - $revenueYesterday) / $revenueYesterday) * 100, 1)
            : null;

        // Quick stats
        $startWeek = $today->copy()->startOfWeek();
        $startMonth = $today->copy()->startOfMonth();
        $quickStats = [
            'revenue_week' => (int) Order::whereBetween('transaction_time', [$startWeek, now()])
                ->where('status', Order::STATUS_PAID)->sum('total_price'),
            'revenue_month' => (int) Order::whereBetween('transaction_time', [$startMonth, now()])
                ->where('status', Order::STATUS_PAID)->sum('total_price'),
            'avg_per_order' => (int) Order::whereDate('transaction_time', $today)
                ->where('status', Order::STATUS_PAID)->avg('total_price') ?? 0,
            'items_sold_today' => (int) Order::whereDate('transaction_time', $today)
                ->where('status', Order::STATUS_PAID)->sum('total_item'),
        ];

        $stats = [
            'revenue_today' => $revenueToday,
            'orders_today' => $ordersToday,
            'total_products' => Product::count(),
            'active_users' => User::where('roles', 'kasir')->where('is_active', true)->count(),
            'revenue_delta' => $revenueDelta,
        ];

        // Top 5 produk (by quantity sold)
        $topProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->with('product:id,name,price,image')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)->get();

        // Recent orders
        $recentOrders = Order::with('kasir:id,name')
            ->latest('transaction_time')
            ->limit(8)->get();

        // Low stock
        $lowStock = Product::where('stock', '<', 5)
            ->orderBy('stock')->limit(10)->get();

        // Payment breakdown today
        $paymentBreakdown = Order::whereDate('transaction_time', $today)
            ->where('status', Order::STATUS_PAID)
            ->select('payment_method', DB::raw('SUM(total_price) as total'))
            ->groupBy('payment_method')->get();

        return view('pages.dashboard', compact(
            'stats','quickStats','topProducts','recentOrders','lowStock','paymentBreakdown'
        ));
    }
}
```

B. ROUTE

2. Update `routes/web.php`:
```php
Route::get('home', [\App\Http\Controllers\DashboardController::class, 'index'])->name('home');
```
(Ganti closure yang return view placeholder.)

C. VIEW

3. Ganti `resources/views/pages/dashboard.blade.php` total:

```blade
@extends('layouts.app')
@section('title', __('Dashboard'))
@section('main')
<section class="section">
    {{-- Greeting --}}
    <div class="section-header">
        <h1>{{ __('Selamat datang, :name', ['name' => auth()->user()->name]) }}</h1>
    </div>
    <p class="text-muted mb-4">{{ formatDate(now(), 'l, d F Y') }}</p>

    {{-- Stat Cards --}}
    <div class="row">
        <div class="col-md-3">
            <x-stat-card
                label="{{ __('Pendapatan Hari Ini') }}"
                value="{{ rupiah($stats['revenue_today']) }}"
                icon="money-bill-wave" color="success"
                :delta="$stats['revenue_delta']" deltaLabel="{{ __('vs kemarin') }}"/>
        </div>
        <div class="col-md-3">
            <x-stat-card
                label="{{ __('Pesanan Hari Ini') }}"
                value="{{ $stats['orders_today'] }}"
                icon="receipt" color="primary"/>
        </div>
        <div class="col-md-3">
            <x-stat-card
                label="{{ __('Total Produk') }}"
                value="{{ $stats['total_products'] }}"
                icon="box-open" color="warning"
                href="{{ route('product.index') }}"/>
        </div>
        <div class="col-md-3">
            <x-stat-card
                label="{{ __('Kasir Aktif') }}"
                value="{{ $stats['active_users'] }}"
                icon="users" color="info"
                href="{{ route('user.index') }}"/>
        </div>
    </div>

    {{-- Quick stats --}}
    <div class="row mt-2">
        <div class="col-md-3"><div class="card-clean"><div class="text-muted small">{{ __('Pendapatan Minggu Ini') }}</div><div class="h4">{{ rupiah($quickStats['revenue_week']) }}</div></div></div>
        <div class="col-md-3"><div class="card-clean"><div class="text-muted small">{{ __('Pendapatan Bulan Ini') }}</div><div class="h4">{{ rupiah($quickStats['revenue_month']) }}</div></div></div>
        <div class="col-md-3"><div class="card-clean"><div class="text-muted small">{{ __('Rata-rata / Order') }}</div><div class="h4">{{ rupiah($quickStats['avg_per_order']) }}</div></div></div>
        <div class="col-md-3"><div class="card-clean"><div class="text-muted small">{{ __('Item Terjual Hari Ini') }}</div><div class="h4">{{ $quickStats['items_sold_today'] }}</div></div></div>
    </div>

    {{-- Top products + Payment chart --}}
    <div class="row mt-4">
        <div class="col-lg-7">
            <div class="card-clean h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ __('Top 5 Produk') }}</h5>
                    <a href="{{ route('product.index') }}" class="small text-primary">{{ __('Lihat semua') }} →</a>
                </div>
                @forelse($topProducts as $i => $item)
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <span class="me-3 fw-bold text-muted">{{ $i+1 }}</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $item->product->name ?? '-' }}</div>
                            <div class="small text-muted">{{ $item->total_sold }} {{ __('terjual') }}</div>
                        </div>
                        <div class="fw-medium">{{ rupiah($item->product->price ?? 0) }}</div>
                    </div>
                @empty
                    <x-empty-state icon="chart-bar" title="{{ __('Belum ada penjualan') }}"/>
                @endforelse
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card-clean h-100">
                <h5 class="mb-3">{{ __('Pembayaran Hari Ini') }}</h5>
                @if($paymentBreakdown->isEmpty())
                    <x-empty-state icon="credit-card" title="{{ __('Belum ada transaksi') }}"/>
                @else
                    <canvas id="paymentChart" height="200"></canvas>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent orders --}}
    <div class="card-clean mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">{{ __('Pesanan Terbaru') }}</h5>
            <a href="{{ route('order.index') }}" class="small text-primary">{{ __('Lihat semua') }} →</a>
        </div>
        @if($recentOrders->isEmpty())
            <x-empty-state icon="receipt" title="{{ __('Belum ada pesanan') }}"/>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="text-uppercase small text-muted">
                        <tr><th>{{ __('Waktu') }}</th><th>{{ __('Order') }}</th><th>{{ __('Kasir') }}</th><th class="text-end">{{ __('Total') }}</th><th class="text-center">{{ __('Status') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $o)
                            <tr>
                                <td>{{ formatDate($o->transaction_time) }}</td>
                                <td><a href="{{ route('order.show', $o) }}" class="text-primary fw-semibold">{{ $o->order_number ?? '#'.$o->id }}</a></td>
                                <td>{{ $o->kasir->name ?? '-' }}</td>
                                <td class="text-end fw-medium">{{ rupiah($o->total_price) }}</td>
                                <td class="text-center"><x-order-status-badge :status="$o->status"/></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Low stock --}}
    <div class="card-clean mt-4">
        <h5 class="mb-3"><i class="fas fa-exclamation-triangle text-warning me-2"></i>{{ __('Stok Menipis') }}</h5>
        @if($lowStock->isEmpty())
            <p class="text-muted mb-0">{{ __('Semua stok aman ✓') }}</p>
        @else
            <div class="row">
                @foreach($lowStock as $p)
                    <div class="col-md-6 mb-2 d-flex justify-content-between align-items-center">
                        <span>{{ $p->name }}</span>
                        <span class="badge bg-{{ $p->stock == 0 ? 'danger' : 'warning' }} text-white">
                            {{ $p->stock }} {{ __('tersisa') }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@push('scripts')
<script src="{{ asset('library/chart.js/Chart.min.js') }}"></script>
<script>
    @if(!$paymentBreakdown->isEmpty())
    const pb = @json($paymentBreakdown);
    new Chart(document.getElementById('paymentChart'), {
        type: 'doughnut',
        data: {
            labels: pb.map(r => r.payment_method.toUpperCase()),
            datasets: [{
                data: pb.map(r => r.total),
                backgroundColor: ['#3B82F6','#10B981','#F59E0B','#EC4899','#8B5CF6']
            }]
        },
        options: { plugins:{ legend:{ position:'bottom' } } }
    });
    @endif
</script>
@endpush
@endsection
```

D. STYLE TWEAK

4. Tambah ke `public/css/app.css`:
```css
.card-clean { background:#fff; border-radius:12px; border:1px solid #F3F4F6; box-shadow:0 1px 2px rgba(0,0,0,.05); padding:1.25rem; }
```

Test dengan login owner → verifikasi 4 stat card + chart + top product + recent orders + low stock muncul dengan data real dari seeder.

Tampilkan output `php artisan route:list --columns=method,uri,name | grep home` setelah selesai.
````

## Hasil yang Diharapkan

```
app/Http/Controllers/DashboardController.php   ← baru
resources/views/pages/dashboard.blade.php       ← total rewrite
public/css/app.css                              ← updated
routes/web.php                                  ← route home pakai controller
```

## Cara Test

```bash
php artisan serve
# Login dengan bahri@fic11.com / 12345678
# Buka /home → dashboard tampil:
# - 4 stat card berisi angka real
# - Quick stats baris kedua
# - Top 5 produk (dengan harga rupiah)
# - Doughnut chart "Pembayaran Hari Ini"
# - 8 recent orders dengan status badge
# - Low stock produk (kalau ada stock<5)

# Test data null (no order today):
# Hapus order hari ini di tinker → buka dashboard → stat card revenue=0, chart hilang & empty state muncul
```

## Penjelasan untuk Murid

Talking points:

1. **"`compact()` vs `with()`?"** — Sama saja. `compact('stats')` = `with('stats', $stats)`. Compact lebih ringkas.

2. **"Kenapa query `SUM(total_price)` di-cast `(int)`?"** — `sum()` Laravel return string atau float kalau decimal. Explicit cast = predictable type untuk view.

3. **"Delta percentage rumusnya?"** — `((today - yesterday) / yesterday) * 100`. Edge case: kalau kemarin 0, set null (skip display).

4. **"`OrderItem::select('product_id', DB::raw(...))`?"** — Aggregate query. Pakai `DB::raw` untuk SQL expression yang Laravel tidak punya helper.

5. **"Pakai `latest('transaction_time')` bukan `created_at`?"** — Karena transaksi historis bisa di-input dengan `transaction_time` lalu (e.g., struk lama). `created_at` = waktu record dibuat di DB, beda dengan kapan transaksi terjadi.

6. **"Chart.js di-load dari local atau CDN?"** — Local lebih reliable (no internet dependency). Sudah di-bundle di Step 04.

7. **"`@push('scripts')` untuk Chart.js?"** — Inject script ke posisi `@stack('scripts')` di layout. Cuma load Chart.js di halaman yang butuh, bukan global.

Pertanyaan reflektif:
- "Dashboard lambat karena query banyak. Optimasi?" (→ cache hasil dengan Redis 5 menit, atau batch ke 1 stored procedure)
- "Kalau ada 3 cabang toko, dashboard per-cabang gimana?" (→ tambah `where('branch_id', ...)` di setiap query + selector di header)

---

**Next: [11-categories-crud.md](11-categories-crud.md)**
