# Dashboard Redesign

## Current Issues

`resources/views/pages/dashboard.blade.php` adalah **template demo Stisla 100% hardcoded**:
- Stat card: "Total Admin: 10", "News: 42", "Reports: 1,201", "Online Users: 47" — semua hardcoded
- Chart "Statistics" pakai `chart.js` tapi data dari `js/page/index-0.js` (dummy)
- "Today's Sales: $243" — USD, hardcoded
- "Recent Activities" → 4 avatar palsu (Farhan, Ujang, Rizal, Alfa)
- Weather widget (`simpleweather.js`) — tidak relevan POS
- "Authors" avatar grid — tidak relevan
- "Referral URL" progress bar dummy
- "Popular Browser" dummy
- "Visitors" world map (jqvmap) dummy
- "This Week Stats" → PlayStation 9, RocketZ, Xiaomay — dummy
- "Tasks" → "Redesign header", "Fix modal window" dummy
- "Quick Draft" form summernote — tidak relevan
- "Latest Posts" → "Laravel 5 Tutorial" 6 baris dummy

Praktis **0% dari isi dashboard relevan dengan POS**.

## Target Dashboard (Phase 1 — Quick win)

### Layout

```
┌──────────────────────────────────────────────────────────┐
│ Selamat datang, {nama}! Berikut ringkasan hari ini.     │
├──────────────────────────────────────────────────────────┤
│ [4 STAT CARDS]                                           │
│ Today's Revenue | Today's Orders | Total Products | Active Users │
├──────────────────────────────────────────────────────────┤
│ [SALES CHART 7/30/90 days]    │ [TOP 5 PRODUCTS]        │
│ Line chart Chart.js           │ List with bar           │
├──────────────────────────────────────────────────────────┤
│ [RECENT ORDERS — last 10]                                │
│ Date | Order# | Kasir | Total | Status                   │
├──────────────────────────────────────────────────────────┤
│ [LOW STOCK ALERT]             │ [PAYMENT METHOD]        │
│ Products stock < 5            │ Pie chart today         │
└──────────────────────────────────────────────────────────┘
```

### Stat Cards (real data)

```php
// app/Http/Controllers/DashboardController.php (baru)
public function index()
{
    $today = today();
    
    $stats = [
        'revenue_today' => Order::whereDate('transaction_time', $today)->sum('total_price'),
        'orders_today' => Order::whereDate('transaction_time', $today)->count(),
        'total_products' => Product::count(),
        'active_users' => User::where('roles', 'kasir')->count(),
    ];
    
    $revenueYesterday = Order::whereDate('transaction_time', $today->copy()->subDay())->sum('total_price');
    $stats['revenue_delta'] = $revenueYesterday > 0
        ? round((($stats['revenue_today'] - $revenueYesterday) / $revenueYesterday) * 100, 1)
        : null;
    
    $salesTrend = Order::selectRaw('DATE(transaction_time) as date, SUM(total_price) as total')
        ->where('transaction_time', '>=', now()->subDays(7))
        ->groupBy('date')->orderBy('date')->get();
    
    $topProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
        ->with('product:id,name,price')
        ->groupBy('product_id')
        ->orderByDesc('total_sold')
        ->limit(5)->get();
    
    $recentOrders = Order::with('kasir:id,name')
        ->latest('transaction_time')->limit(10)->get();
    
    $lowStock = Product::where('stock', '<', 5)->orderBy('stock')->limit(10)->get();
    
    $paymentBreakdown = Order::whereDate('transaction_time', $today)
        ->select('payment_method', DB::raw('SUM(total_price) as total'))
        ->groupBy('payment_method')->get();
    
    return view('pages.dashboard', compact(
        'stats', 'salesTrend', 'topProducts', 'recentOrders', 'lowStock', 'paymentBreakdown'
    ));
}
```

### View (`pages/dashboard.blade.php` baru)

```blade
@extends('layouts.app')
@section('title', __('Dashboard'))

@section('main')
    <div class="space-y-6">
        {{-- Greeting --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                {{ __('Selamat datang, :name 👋', ['name' => auth()->user()->name]) }}
            </h1>
            <p class="text-gray-500 mt-1">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        
        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card 
                label="{{ __('Pendapatan Hari Ini') }}"
                value="Rp {{ number_format($stats['revenue_today'], 0, ',', '.') }}"
                icon="banknotes"
                color="success"
                :delta="$stats['revenue_delta']"
            />
            <x-stat-card 
                label="{{ __('Pesanan Hari Ini') }}"
                value="{{ $stats['orders_today'] }}"
                icon="receipt"
                color="primary"
            />
            <x-stat-card 
                label="{{ __('Total Produk') }}"
                value="{{ $stats['total_products'] }}"
                icon="cube"
                color="warning"
            />
            <x-stat-card 
                label="{{ __('Kasir Aktif') }}"
                value="{{ $stats['active_users'] }}"
                icon="users"
                color="info"
            />
        </div>
        
        {{-- Chart + Top Products --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold">{{ __('Tren Penjualan') }}</h3>
                    <div class="btn-group">
                        <button class="px-3 py-1 text-sm bg-primary-50 text-primary-600 rounded" data-range="7">7D</button>
                        <button class="px-3 py-1 text-sm" data-range="30">30D</button>
                        <button class="px-3 py-1 text-sm" data-range="90">90D</button>
                    </div>
                </div>
                <canvas id="salesChart" height="100"></canvas>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-semibold mb-4">{{ __('Top 5 Produk') }}</h3>
                <ul class="space-y-3">
                    @forelse ($topProducts as $i => $item)
                        <li class="flex items-center">
                            <span class="w-6 h-6 rounded bg-primary-100 text-primary-600 text-xs flex items-center justify-center font-bold mr-3">
                                {{ $i + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ $item->product->name }}</p>
                                <p class="text-xs text-gray-500">{{ $item->total_sold }} terjual</p>
                            </div>
                            <span class="text-sm font-medium">Rp {{ number_format($item->product->price, 0, ',', '.') }}</span>
                        </li>
                    @empty
                        <p class="text-sm text-gray-400">{{ __('Belum ada penjualan') }}</p>
                    @endforelse
                </ul>
            </div>
        </div>
        
        {{-- Recent Orders --}}
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold">{{ __('Pesanan Terbaru') }}</h3>
                <a href="{{ route('order.index') }}" class="text-sm text-primary-600 hover:underline">
                    {{ __('Lihat semua') }} →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500 border-b">
                        <tr>
                            <th class="py-2 text-left">{{ __('Waktu') }}</th>
                            <th class="text-left">{{ __('Order') }}</th>
                            <th class="text-left">{{ __('Kasir') }}</th>
                            <th class="text-right">{{ __('Total') }}</th>
                            <th class="text-center">{{ __('Pembayaran') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr class="border-b last:border-0 hover:bg-gray-50">
                                <td class="py-3">{{ $order->transaction_time->translatedFormat('d M Y H:i') }}</td>
                                <td><a href="{{ route('order.show', $order) }}" class="text-primary-600">#{{ $order->id }}</a></td>
                                <td>{{ $order->kasir->name }}</td>
                                <td class="text-right font-medium">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100">{{ $order->payment_method }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-8 text-gray-400">{{ __('Belum ada pesanan hari ini') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Low stock + Payment breakdown --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-semibold mb-4 flex items-center">
                    <x-icon name="exclamation-triangle" class="w-5 h-5 text-warning-500 mr-2"/>
                    {{ __('Stok Menipis') }}
                </h3>
                @forelse ($lowStock as $product)
                    <div class="flex justify-between items-center py-2 border-b last:border-0">
                        <span class="text-sm">{{ $product->name }}</span>
                        <span class="px-2 py-1 text-xs rounded-full bg-danger-100 text-danger-600">
                            {{ $product->stock }} {{ __('tersisa') }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">{{ __('Semua stok aman ✓') }}</p>
                @endforelse
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-semibold mb-4">{{ __('Pembayaran Hari Ini') }}</h3>
                <canvas id="paymentChart" height="120"></canvas>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('library/chart.js/dist/Chart.min.js') }}"></script>
<script>
    const salesData = @json($salesTrend);
    const paymentData = @json($paymentBreakdown);
    // initialize charts...
</script>
@endpush
```

## Action Items

- [ ] Buat `DashboardController` (saat ini home pakai closure di `routes/web.php`)
- [ ] Buat `<x-stat-card>` blade component
- [ ] Hapus seluruh dummy section (weather, authors, referral, browser, visitors, tasks, quick draft, latest posts)
- [ ] Implement query untuk: revenue today, orders today, top products, recent orders, low stock, payment breakdown
- [ ] Sales chart dengan toggle 7/30/90 hari (initial: 7 hari, sisanya via AJAX)
- [ ] Payment method pie chart
- [ ] Translasi semua string ke `lang/id/messages.php`
- [ ] Cast `transaction_time` ke datetime di Order model (untuk `translatedFormat`)
- [ ] Setup Carbon locale `id` di `AppServiceProvider::boot()`: `Carbon::setLocale('id')`

## Dashboard Phase 2 enhancement

- Filter periode kustom (date range picker)
- Comparison mode: minggu ini vs minggu lalu
- Export dashboard ke PDF report
- Real-time refresh (Livewire / Laravel Echo + Reverb)
