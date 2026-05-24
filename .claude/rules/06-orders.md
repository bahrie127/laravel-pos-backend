# Orders & Order Detail Enhancement

## Current State

### `pages/orders/index.blade.php`
- Kolom: Transaction Time, Total Price, Total Item, Kasir
- Tidak ada search, tidak ada filter
- Tidak ada status badge, tidak ada payment method column
- Total Price tampil raw (tanpa Rupiah format)
- Transaction time raw datetime
- Klik baris → detail (link di kolom Transaction Time)
- Tidak ada empty state, no export

### `pages/orders/view.blade.php`
- Header: "Order Detail" + breadcrumb
- Info inline: Total Price, Transaction Time, Total Item (tanpa Rupiah format)
- Tabel order items: Product Name, Price, Quantity, Total Price
- Tidak ada kasir info, tidak ada payment method, tidak ada print struk, tidak ada export
- Tidak ada subtotal, tax, discount breakdown

## Target Enhancement

### Schema additions (migration)

```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('order_number')->unique()->after('id'); // INV-20260524-0001
    $table->enum('status', ['pending', 'paid', 'cancelled', 'refunded'])->default('paid')->after('payment_method');
    $table->decimal('subtotal', 12, 2)->default(0)->after('status');
    $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
    $table->decimal('tax', 12, 2)->default(0)->after('discount');
    $table->decimal('amount_paid', 12, 2)->default(0)->after('tax');
    $table->decimal('change_amount', 12, 2)->default(0)->after('amount_paid');
    $table->string('customer_name')->nullable()->after('change_amount');
    $table->text('notes')->nullable()->after('customer_name');
});
```

### Index page (target)

```blade
@extends('layouts.app')
@section('title', __('Pesanan'))

@section('main')
<div class="space-y-4">
    {{-- Page header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">{{ __('Pesanan') }}</h1>
            <p class="text-sm text-gray-500">
                {{ __(':total transaksi · Rp :total_revenue', [
                    'total' => $orders->total(),
                    'total_revenue' => number_format($totalRevenue, 0, ',', '.')
                ]) }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('order.export', request()->query()) }}" class="btn btn-secondary">
                <x-icon name="arrow-down-tray" class="w-4 h-4 mr-2"/> {{ __('Export Excel') }}
            </a>
        </div>
    </div>
    
    {{-- Filter bar --}}
    <div class="card">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="text" name="q" value="{{ request('q') }}" 
                   placeholder="{{ __('Cari order # atau customer...') }}"
                   class="form-input md:col-span-2">
            
            <input type="text" name="date_range" value="{{ request('date_range') }}" 
                   placeholder="{{ __('Pilih tanggal') }}"
                   class="form-input daterange-picker">
            
            <select name="payment_method" class="form-select">
                <option value="">{{ __('Semua Pembayaran') }}</option>
                <option value="cash" @selected(request('payment_method') == 'cash')>{{ __('Tunai') }}</option>
                <option value="qris" @selected(request('payment_method') == 'qris')>QRIS</option>
                <option value="transfer" @selected(request('payment_method') == 'transfer')>Transfer</option>
            </select>
            
            <select name="kasir_id" class="form-select">
                <option value="">{{ __('Semua Kasir') }}</option>
                @foreach ($kasirList as $k)
                    <option value="{{ $k->id }}" @selected(request('kasir_id') == $k->id)>{{ $k->name }}</option>
                @endforeach
            </select>
            
            <div class="md:col-span-5 flex gap-2">
                <button class="btn btn-primary">{{ __('Filter') }}</button>
                <a href="{{ route('order.index') }}" class="btn btn-ghost">{{ __('Reset') }}</a>
            </div>
        </form>
    </div>
    
    {{-- Table --}}
    <div class="card overflow-hidden">
        @if ($orders->count() > 0)
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="p-3 text-left">{{ __('Order #') }}</th>
                        <th class="p-3 text-left">{{ __('Tanggal') }}</th>
                        <th class="p-3 text-left">{{ __('Kasir') }}</th>
                        <th class="p-3 text-left">{{ __('Customer') }}</th>
                        <th class="p-3 text-center">{{ __('Items') }}</th>
                        <th class="p-3 text-right">{{ __('Total') }}</th>
                        <th class="p-3 text-center">{{ __('Pembayaran') }}</th>
                        <th class="p-3 text-center">{{ __('Status') }}</th>
                        <th class="p-3 text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3">
                                <a href="{{ route('order.show', $order) }}" class="text-primary-600 font-medium">
                                    {{ $order->order_number ?? '#'.$order->id }}
                                </a>
                            </td>
                            <td class="p-3">{{ $order->transaction_time->translatedFormat('d M Y H:i') }}</td>
                            <td class="p-3">{{ $order->kasir->name }}</td>
                            <td class="p-3">{{ $order->customer_name ?? '—' }}</td>
                            <td class="p-3 text-center">{{ $order->total_item }}</td>
                            <td class="p-3 text-right font-medium">
                                Rp {{ number_format($order->total_price, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center">
                                <span class="badge badge-gray">{{ strtoupper($order->payment_method) }}</span>
                            </td>
                            <td class="p-3 text-center">
                                <x-order-status-badge :status="$order->status"/>
                            </td>
                            <td class="p-3">
                                <div class="flex justify-center gap-1">
                                    <a href="{{ route('order.show', $order) }}" class="btn-icon" title="Lihat">
                                        <x-icon name="eye" class="w-4 h-4"/>
                                    </a>
                                    <a href="{{ route('order.receipt', $order) }}" class="btn-icon" title="Cetak Struk" target="_blank">
                                        <x-icon name="printer" class="w-4 h-4"/>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="p-4 border-t flex justify-between items-center">
                <p class="text-sm text-gray-500">
                    {{ __('Menampilkan :from-:to dari :total', [...]) }}
                </p>
                {{ $orders->withQueryString()->links() }}
            </div>
        @else
            <x-empty-state 
                icon="receipt"
                title="{{ __('Belum ada pesanan') }}"
                description="{{ __('Pesanan dari aplikasi kasir akan tampil di sini.') }}"
            />
        @endif
    </div>
</div>
@endsection
```

### Order Detail page (target)

Layout: 2-column. Kiri item detail, kanan summary + customer + actions.

```blade
@extends('layouts.app')
@section('title', __('Pesanan #:no', ['no' => $order->order_number ?? $order->id]))

@section('main')
<div class="space-y-4">
    {{-- Header with actions --}}
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-bold">{{ $order->order_number ?? '#'.$order->id }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $order->transaction_time->translatedFormat('l, d F Y · H:i') }}
                · <x-order-status-badge :status="$order->status"/>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('order.receipt', $order) }}" target="_blank" class="btn btn-secondary">
                <x-icon name="printer" class="w-4 h-4 mr-2"/> {{ __('Cetak Struk') }}
            </a>
            <a href="{{ route('order.invoice-pdf', $order) }}" class="btn btn-secondary">
                <x-icon name="document-arrow-down" class="w-4 h-4 mr-2"/> PDF
            </a>
            @can('refund', $order)
                <button class="btn btn-danger">{{ __('Refund') }}</button>
            @endcan
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: items --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <h3 class="font-semibold mb-4">{{ __('Item Pesanan') }}</h3>
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500 border-b">
                        <tr>
                            <th class="py-2 text-left">{{ __('Produk') }}</th>
                            <th class="text-right">{{ __('Harga') }}</th>
                            <th class="text-center">{{ __('Qty') }}</th>
                            <th class="text-right">{{ __('Subtotal') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orderItems as $item)
                            <tr class="border-b last:border-0">
                                <td class="py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($item->product->image)
                                            <img src="{{ asset('storage/products/' . $item->product->image) }}" class="w-10 h-10 rounded"/>
                                        @endif
                                        <div>
                                            <p class="font-medium">{{ $item->product->name }}</p>
                                            <p class="text-xs text-gray-500">SKU #{{ $item->product->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right">Rp {{ number_format($item->product->price, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right font-medium">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if ($order->notes)
                <div class="card">
                    <h3 class="font-semibold mb-2">{{ __('Catatan') }}</h3>
                    <p class="text-sm text-gray-700">{{ $order->notes }}</p>
                </div>
            @endif
        </div>
        
        {{-- Right: summary --}}
        <div class="space-y-6">
            <div class="card">
                <h3 class="font-semibold mb-4">{{ __('Ringkasan') }}</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('Subtotal') }}</dt>
                        <dd>Rp {{ number_format($order->subtotal ?? $order->total_price, 0, ',', '.') }}</dd>
                    </div>
                    @if ($order->discount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('Diskon') }}</dt>
                            <dd class="text-danger-600">-Rp {{ number_format($order->discount, 0, ',', '.') }}</dd>
                        </div>
                    @endif
                    @if ($order->tax > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('Pajak') }}</dt>
                            <dd>Rp {{ number_format($order->tax, 0, ',', '.') }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between font-semibold text-lg pt-2 border-t">
                        <dt>{{ __('Total') }}</dt>
                        <dd>Rp {{ number_format($order->total_price, 0, ',', '.') }}</dd>
                    </div>
                </dl>
                
                <div class="mt-4 pt-4 border-t space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('Pembayaran') }}</span>
                        <span class="font-medium">{{ strtoupper($order->payment_method) }}</span>
                    </div>
                    @if ($order->amount_paid > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ __('Dibayar') }}</span>
                            <span>Rp {{ number_format($order->amount_paid, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ __('Kembalian') }}</span>
                            <span>Rp {{ number_format($order->change_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="card">
                <h3 class="font-semibold mb-4">{{ __('Info') }}</h3>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('Kasir') }}</dt>
                        <dd class="font-medium">{{ $order->kasir->name }}</dd>
                    </div>
                    @if ($order->customer_name)
                        <div>
                            <dt class="text-gray-500">{{ __('Customer') }}</dt>
                            <dd class="font-medium">{{ $order->customer_name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-gray-500">{{ __('Total Item') }}</dt>
                        <dd class="font-medium">{{ $order->total_item }} {{ __('item') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
```

### Order Receipt (thermal print)

Buat view khusus `pages/orders/receipt.blade.php` dengan CSS @media print untuk thermal printer 80mm:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Struk #{{ $order->order_number }}</title>
    <style>
        @media print {
            body { width: 80mm; font-family: monospace; font-size: 12px; }
            .no-print { display: none; }
        }
        body { padding: 10px; }
    </style>
</head>
<body onload="window.print()">
    <div class="text-center">
        <h2>{{ config('app.name') }}</h2>
        <p>{{ config('app.address') }}</p>
        <hr>
    </div>
    <p>Order #{{ $order->order_number }}</p>
    <p>{{ $order->transaction_time->format('d/m/Y H:i') }}</p>
    <p>Kasir: {{ $order->kasir->name }}</p>
    <hr>
    @foreach ($orderItems as $item)
        <div>
            {{ $item->product->name }}<br>
            {{ $item->quantity }} x {{ number_format($item->product->price) }} = {{ number_format($item->total_price) }}
        </div>
    @endforeach
    <hr>
    <p>Total: Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
    <p>Bayar: Rp {{ number_format($order->amount_paid, 0, ',', '.') }}</p>
    <p>Kembali: Rp {{ number_format($order->change_amount, 0, ',', '.') }}</p>
    <hr>
    <p class="text-center">Terima kasih!</p>
</body>
</html>
```

### Controller changes

```php
public function index(Request $request)
{
    $query = Order::query()->with('kasir:id,name');
    
    if ($request->filled('q')) {
        $query->where(function ($q) use ($request) {
            $q->where('order_number', 'like', "%{$request->q}%")
              ->orWhere('customer_name', 'like', "%{$request->q}%");
        });
    }
    if ($request->filled('date_range')) {
        [$from, $to] = explode(' - ', $request->date_range);
        $query->whereBetween('transaction_time', [$from, $to]);
    }
    if ($request->filled('payment_method')) {
        $query->where('payment_method', $request->payment_method);
    }
    if ($request->filled('kasir_id')) {
        $query->where('kasir_id', $request->kasir_id);
    }
    
    $orders = $query->latest('transaction_time')->paginate(20);
    $totalRevenue = (clone $query)->sum('total_price');
    $kasirList = User::where('roles', 'kasir')->get(['id', 'name']);
    
    return view('pages.orders.index', compact('orders', 'totalRevenue', 'kasirList'));
}

public function receipt(Order $order)
{
    $orderItems = $order->orderItems()->with('product')->get();
    return view('pages.orders.receipt', compact('order', 'orderItems'));
}

public function invoicePdf(Order $order)
{
    $pdf = Pdf::loadView('pages.orders.invoice-pdf', compact('order'));
    return $pdf->download("invoice-{$order->order_number}.pdf");
}

public function export(Request $request)
{
    return Excel::download(new OrdersExport($request->all()), 'orders-' . now()->format('Ymd') . '.xlsx');
}
```

### Model

```php
// app/Models/Order.php
protected $casts = [
    'transaction_time' => 'datetime',
    'total_price' => 'decimal:2',
    'subtotal' => 'decimal:2',
    'discount' => 'decimal:2',
    'tax' => 'decimal:2',
];

protected static function booted()
{
    static::creating(function ($order) {
        if (empty($order->order_number)) {
            $order->order_number = 'INV-' . now()->format('Ymd') . '-' . str_pad(
                Order::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
            );
        }
    });
}
```

## Action Items

- [ ] Migration: order_number, status, subtotal/discount/tax/amount_paid/change_amount, customer_name, notes
- [ ] Auto-generate order_number di model `creating`
- [ ] Cast transaction_time ke datetime
- [ ] Index page: filter date range, payment method, kasir, search by order# or customer
- [ ] Status badge component (`<x-order-status-badge>`)
- [ ] Total revenue summary di header
- [ ] Detail page: 2-column layout dengan ringkasan, customer info, kasir info
- [ ] Cetak struk thermal 80mm
- [ ] Cetak invoice PDF (A4) — pakai dompdf
- [ ] Export Excel dengan filter aktif
- [ ] Refund action (Phase 4, butuh policy + log)
- [ ] Route baru: `order.receipt`, `order.invoice-pdf`, `order.export`
- [ ] API endpoint juga ikut return `order_number`, `status`, breakdown
