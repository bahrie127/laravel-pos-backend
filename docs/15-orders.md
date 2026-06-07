# Step 15 — Orders: List, Detail, Receipt Thermal, Invoice PDF, Export Excel

## Tujuan

Manajemen orders **lengkap**: filter multi-dimensi, detail page 2-kolom, cetak struk thermal (80mm), invoice PDF (A4), export Excel.

## Prasyarat

- Step 14 selesai
- Composer sudah punya `barryvdh/laravel-dompdf` + `maatwebsite/excel`

## Konteks

Order = output utama dari kasir POS. Page ini paling sering dilihat owner/admin. Detail harus rapi (cetak struk + invoice), list harus mudah filter.

## Prompt untuk AI

````
Project Laravel POS sudah punya CRUD master data + profile. Sekarang implement Orders.

A. ROUTE

1. `routes/web.php`:
```php
Route::get('order/export', [\App\Http\Controllers\OrderController::class, 'export'])->name('order.export');
Route::get('order/{order}/receipt', [\App\Http\Controllers\OrderController::class, 'receipt'])->name('order.receipt');
Route::get('order/{order}/invoice-pdf', [\App\Http\Controllers\OrderController::class, 'invoicePdf'])->name('order.invoice-pdf');
Route::resource('order', \App\Http\Controllers\OrderController::class)->only(['index','show','destroy']);
```

B. CONTROLLER

2. `app/Http/Controllers/OrderController.php`:
```php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Exports\OrdersExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller {
    public function index(Request $request) {
        $query = Order::with('kasir:id,name');

        // Kasir hanya lihat order sendiri
        if (! $request->user()->isAdmin()) {
            $query->where('kasir_id', $request->user()->id);
        }

        if ($request->filled('q')) {
            $query->where(fn($q) => $q->where('order_number','like','%'.$request->q.'%')
                ->orWhere('customer_name','like','%'.$request->q.'%'));
        }
        if ($request->filled('date_from')) $query->whereDate('transaction_time','>=',$request->date_from);
        if ($request->filled('date_to')) $query->whereDate('transaction_time','<=',$request->date_to);
        if ($request->filled('payment_method')) $query->where('payment_method', $request->payment_method);
        if ($request->filled('kasir_id') && $request->user()->isAdmin()) $query->where('kasir_id', $request->kasir_id);
        if ($request->filled('status')) $query->where('status', $request->status);

        $orders = $query->latest('transaction_time')->paginate(20)->withQueryString();
        $totalRevenue = (int) (clone $query->getQuery())->where('status', Order::STATUS_PAID)->sum('total_price');
        $totalOrders = $orders->total();

        $kasirList = $request->user()->isAdmin()
            ? User::where('roles','kasir')->orderBy('name')->get(['id','name'])
            : collect();
        $paymentMethods = ['cash','qris','transfer'];

        return view('pages.orders.index', compact('orders','totalRevenue','totalOrders','kasirList','paymentMethods'));
    }

    public function show(Order $order) {
        $this->authorize('view', $order);
        $orderItems = $order->orderItems()->with('product')->get();
        return view('pages.orders.view', compact('order','orderItems'));
    }

    public function destroy(Order $order) {
        $this->authorize('delete', $order);
        $order->delete();
        return redirect()->route('order.index')->with('success', __('messages.deleted', ['resource' => 'Pesanan']));
    }

    public function receipt(Order $order) {
        $this->authorize('view', $order);
        $orderItems = $order->orderItems()->with('product')->get();
        return view('pages.orders.receipt', compact('order','orderItems'));
    }

    public function invoicePdf(Order $order) {
        $this->authorize('view', $order);
        $orderItems = $order->orderItems()->with('product')->get();
        $pdf = Pdf::loadView('pages.orders.invoice-pdf', compact('order','orderItems'))->setPaper('A4');
        return $pdf->download($order->order_number.'.pdf');
    }

    public function export(Request $request) {
        return Excel::download(new OrdersExport($request->all()), 'orders-'.now()->format('Ymd').'.xlsx');
    }
}
```

C. EXPORTER

3. `app/Exports/OrdersExport.php`:
```php
namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize {
    public function __construct(protected array $filters) {}

    public function query() {
        $query = Order::with('kasir:id,name');
        if (! empty($this->filters['q'])) {
            $query->where(fn($q) => $q->where('order_number','like','%'.$this->filters['q'].'%')
                ->orWhere('customer_name','like','%'.$this->filters['q'].'%'));
        }
        if (! empty($this->filters['date_from'])) $query->whereDate('transaction_time','>=',$this->filters['date_from']);
        if (! empty($this->filters['date_to'])) $query->whereDate('transaction_time','<=',$this->filters['date_to']);
        if (! empty($this->filters['payment_method'])) $query->where('payment_method',$this->filters['payment_method']);
        if (! empty($this->filters['kasir_id'])) $query->where('kasir_id',$this->filters['kasir_id']);
        if (! empty($this->filters['status'])) $query->where('status',$this->filters['status']);
        return $query->latest('transaction_time');
    }

    public function headings(): array {
        return ['Order #','Tanggal','Kasir','Customer','Items','Subtotal','Diskon','Pajak','Total','Pembayaran','Status'];
    }

    public function map($order): array {
        return [
            $order->order_number,
            $order->transaction_time->format('Y-m-d H:i'),
            $order->kasir->name ?? '',
            $order->customer_name,
            $order->total_item,
            $order->subtotal,
            $order->discount,
            $order->tax,
            $order->total_price,
            strtoupper($order->payment_method),
            $order->statusLabel(),
        ];
    }

    public function styles(Worksheet $sheet) {
        return [1 => ['font' => ['bold' => true]]];
    }
}
```

D. VIEWS

4. `pages/orders/index.blade.php`:
```blade
@extends('layouts.app')
@section('title', __('Pesanan'))
@section('main')
<section class="section">
    <x-page-header title="{{ __('Pesanan') }}"
        subtitle="{{ __(':total transaksi · :revenue', ['total' => $totalOrders, 'revenue' => rupiah($totalRevenue)]) }}">
        <x-slot:actions>
            <a href="{{ route('order.export', request()->query()) }}" class="btn btn-outline-success"><i class="fas fa-file-excel me-2"></i>{{ __('Export Excel') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card-clean mb-3">
        <form method="GET" class="row g-2">
            <div class="col-md-3"><input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Order # / customer...') }}" class="form-control"></div>
            <div class="col-md-2"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
            <div class="col-md-2"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
            <div class="col-md-2">
                <select name="payment_method" class="form-control">
                    <option value="">{{ __('Semua Pembayaran') }}</option>
                    @foreach($paymentMethods as $pm)<option value="{{ $pm }}" @selected(request('payment_method')==$pm)>{{ strtoupper($pm) }}</option>@endforeach
                </select>
            </div>
            @if($kasirList->isNotEmpty())
            <div class="col-md-2">
                <select name="kasir_id" class="form-control">
                    <option value="">{{ __('Semua Kasir') }}</option>
                    @foreach($kasirList as $k)<option value="{{ $k->id }}" @selected(request('kasir_id')==$k->id)>{{ $k->name }}</option>@endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <select name="status" class="form-control">
                    <option value="">{{ __('Semua Status') }}</option>
                    @foreach(['pending','paid','cancelled','refunded'] as $s)<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('Filter') }}</button></div>
        </form>
    </div>

    <div class="card-clean">
        @if($orders->isEmpty())
            <x-empty-state icon="receipt" title="{{ __('Belum ada pesanan') }}"/>
        @else
            <table class="table align-middle">
                <thead class="text-uppercase small text-muted">
                    <tr><th>Order #</th><th>{{ __('Tanggal') }}</th><th>{{ __('Kasir') }}</th><th>{{ __('Customer') }}</th><th class="text-center">{{ __('Items') }}</th><th class="text-end">{{ __('Total') }}</th><th class="text-center">{{ __('Pembayaran') }}</th><th class="text-center">{{ __('Status') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($orders as $o)
                        <tr>
                            <td><a href="{{ route('order.show', $o) }}" class="fw-semibold text-primary">{{ $o->order_number }}</a></td>
                            <td>{{ formatDate($o->transaction_time) }}</td>
                            <td>{{ $o->kasir->name ?? '-' }}</td>
                            <td>{{ $o->customer_name ?? '—' }}</td>
                            <td class="text-center">{{ $o->total_item }}</td>
                            <td class="text-end fw-medium">{{ rupiah($o->total_price) }}</td>
                            <td class="text-center"><span class="badge bg-light text-dark">{{ strtoupper($o->payment_method) }}</span></td>
                            <td class="text-center"><x-order-status-badge :status="$o->status"/></td>
                            <td>
                                <a href="{{ route('order.show', $o) }}" class="btn btn-sm btn-light" title="Lihat"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('order.receipt', $o) }}" target="_blank" class="btn btn-sm btn-light" title="Cetak Struk"><i class="fas fa-print"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $orders->links() }}</div>
        @endif
    </div>
</section>
@endsection
```

5. `pages/orders/view.blade.php` — 2-column detail:
```blade
@extends('layouts.app')
@section('title', __('Pesanan :no', ['no' => $order->order_number]))
@section('main')
<section class="section">
    <x-page-header title="{{ $order->order_number }}"
        subtitle="{{ formatDate($order->transaction_time, 'l, d F Y · H:i') }}"
        :breadcrumbs="[['label'=>__('Pesanan'),'url'=>route('order.index')], ['label'=>$order->order_number]]">
        <x-slot:actions>
            <span class="badge bg-{{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
            <a href="{{ route('order.receipt', $order) }}" target="_blank" class="btn btn-outline-secondary"><i class="fas fa-print me-2"></i>{{ __('Cetak Struk') }}</a>
            <a href="{{ route('order.invoice-pdf', $order) }}" class="btn btn-outline-secondary"><i class="fas fa-file-pdf me-2"></i>PDF</a>
            @can('refund', $order)
                <button class="btn btn-warning">{{ __('Refund') }}</button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="row">
        <div class="col-lg-8">
            <div class="card-clean">
                <h5 class="mb-3">{{ __('Item Pesanan') }}</h5>
                <table class="table align-middle">
                    <thead class="text-uppercase small text-muted">
                        <tr><th>{{ __('Produk') }}</th><th class="text-end">{{ __('Harga') }}</th><th class="text-center">{{ __('Qty') }}</th><th class="text-end">{{ __('Subtotal') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach($orderItems as $it)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($it->product?->image)
                                            <img src="{{ asset('storage/products/'.$it->product->image) }}" class="rounded me-2" style="width:40px;height:40px;object-fit:cover">
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $it->product?->name ?? '[deleted]' }}</div>
                                            <div class="small text-muted">SKU #{{ $it->product?->id ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">{{ rupiah($it->product?->price ?? 0) }}</td>
                                <td class="text-center">{{ $it->quantity }}</td>
                                <td class="text-end fw-medium">{{ rupiah($it->total_price) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($order->notes)
                <div class="card-clean mt-3"><h6>{{ __('Catatan') }}</h6><p class="mb-0 text-muted">{{ $order->notes }}</p></div>
            @endif
        </div>
        <div class="col-lg-4">
            <div class="card-clean mb-3">
                <h5 class="mb-3">{{ __('Ringkasan') }}</h5>
                <dl class="row mb-0">
                    <dt class="col-6 text-muted">{{ __('Subtotal') }}</dt><dd class="col-6 text-end">{{ rupiah($order->subtotal) }}</dd>
                    @if($order->discount > 0)
                        <dt class="col-6 text-muted">{{ __('Diskon') }}</dt><dd class="col-6 text-end text-danger">-{{ rupiah($order->discount) }}</dd>
                    @endif
                    @if($order->tax > 0)
                        <dt class="col-6 text-muted">{{ __('Pajak') }}</dt><dd class="col-6 text-end">{{ rupiah($order->tax) }}</dd>
                    @endif
                    <dt class="col-6 fw-bold border-top pt-2 mt-2">{{ __('Total') }}</dt>
                    <dd class="col-6 text-end fw-bold border-top pt-2 mt-2 h5">{{ rupiah($order->total_price) }}</dd>
                </dl>
                <hr>
                <dl class="row mb-0">
                    <dt class="col-6 text-muted">{{ __('Pembayaran') }}</dt><dd class="col-6 text-end">{{ strtoupper($order->payment_method) }}</dd>
                    @if($order->amount_paid > 0)
                        <dt class="col-6 text-muted">{{ __('Dibayar') }}</dt><dd class="col-6 text-end">{{ rupiah($order->amount_paid) }}</dd>
                        <dt class="col-6 text-muted">{{ __('Kembalian') }}</dt><dd class="col-6 text-end">{{ rupiah($order->change_amount) }}</dd>
                    @endif
                </dl>
            </div>
            <div class="card-clean">
                <h5 class="mb-3">{{ __('Info') }}</h5>
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">{{ __('Kasir') }}</dt><dd class="col-7">{{ $order->kasir->name ?? '-' }}</dd>
                    @if($order->customer_name)
                        <dt class="col-5 text-muted">{{ __('Customer') }}</dt><dd class="col-7">{{ $order->customer_name }}</dd>
                    @endif
                    <dt class="col-5 text-muted">{{ __('Total Item') }}</dt><dd class="col-7">{{ $order->total_item }}</dd>
                </dl>
            </div>
        </div>
    </div>
</section>
@endsection
```

6. `pages/orders/receipt.blade.php` (thermal 80mm):
```blade
<!DOCTYPE html><html><head>
<title>Struk #{{ $order->order_number }}</title>
<style>
@page { size: 80mm auto; margin: 5mm; }
body { width: 70mm; font-family: monospace; font-size: 11px; padding: 5px; }
.center { text-align: center; }
.right { text-align: right; }
hr { border: none; border-top: 1px dashed #000; }
table { width: 100%; }
@media print { .no-print { display: none; } }
</style>
</head><body onload="window.print()">
<div class="center">
    <strong>{{ config('app.name') }}</strong><br>
    Jl. Contoh No. 1, Jakarta
</div>
<hr>
<div>{{ $order->order_number }}</div>
<div>{{ formatDate($order->transaction_time, 'd/m/Y H:i') }}</div>
<div>Kasir: {{ $order->kasir->name ?? '-' }}</div>
@if($order->customer_name)<div>Customer: {{ $order->customer_name }}</div>@endif
<hr>
@foreach($orderItems as $it)
<div>
    {{ $it->product->name ?? '-' }}<br>
    {{ $it->quantity }} x {{ rupiah($it->product->price ?? 0, false) }} = {{ rupiah($it->total_price, false) }}
</div>
@endforeach
<hr>
<table>
    <tr><td>Subtotal</td><td class="right">{{ rupiah($order->subtotal, false) }}</td></tr>
    @if($order->discount > 0)<tr><td>Diskon</td><td class="right">-{{ rupiah($order->discount, false) }}</td></tr>@endif
    @if($order->tax > 0)<tr><td>Pajak</td><td class="right">{{ rupiah($order->tax, false) }}</td></tr>@endif
    <tr><td><strong>Total</strong></td><td class="right"><strong>{{ rupiah($order->total_price, false) }}</strong></td></tr>
    <tr><td>Bayar ({{ strtoupper($order->payment_method) }})</td><td class="right">{{ rupiah($order->amount_paid, false) }}</td></tr>
    <tr><td>Kembali</td><td class="right">{{ rupiah($order->change_amount, false) }}</td></tr>
</table>
<hr>
<div class="center">Terima kasih atas kunjungan Anda</div>
<button class="no-print" onclick="window.print()">Cetak</button>
</body></html>
```

7. `pages/orders/invoice-pdf.blade.php` (A4 PDF):
```blade
<!DOCTYPE html><html><head><meta charset="UTF-8">
<title>Invoice {{ $order->order_number }}</title>
<style>
body { font-family: sans-serif; font-size: 12px; color: #333; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.h1 { font-size: 24px; font-weight: bold; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; border-bottom: 1px solid #eee; text-align: left; }
.right { text-align: right; }
.summary { margin-top: 20px; }
.total-row { font-size: 16px; font-weight: bold; border-top: 2px solid #333; }
</style>
</head><body>
<div class="header">
    <div>
        <div class="h1">{{ config('app.name') }}</div>
        <div>Invoice</div>
    </div>
    <div class="right">
        <div><strong>{{ $order->order_number }}</strong></div>
        <div>{{ formatDate($order->transaction_time, 'd F Y H:i') }}</div>
        <div>Kasir: {{ $order->kasir->name ?? '-' }}</div>
    </div>
</div>

@if($order->customer_name)<p>Kepada: <strong>{{ $order->customer_name }}</strong></p>@endif

<table>
    <thead><tr><th>Produk</th><th class="right">Harga</th><th class="right">Qty</th><th class="right">Subtotal</th></tr></thead>
    <tbody>
        @foreach($orderItems as $it)
            <tr>
                <td>{{ $it->product->name ?? '-' }}</td>
                <td class="right">{{ rupiah($it->product->price ?? 0) }}</td>
                <td class="right">{{ $it->quantity }}</td>
                <td class="right">{{ rupiah($it->total_price) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="summary" style="width: 40%; margin-left: auto;">
    <tr><td>Subtotal</td><td class="right">{{ rupiah($order->subtotal) }}</td></tr>
    @if($order->discount > 0)<tr><td>Diskon</td><td class="right">-{{ rupiah($order->discount) }}</td></tr>@endif
    @if($order->tax > 0)<tr><td>Pajak</td><td class="right">{{ rupiah($order->tax) }}</td></tr>@endif
    <tr class="total-row"><td>Total</td><td class="right">{{ rupiah($order->total_price) }}</td></tr>
</table>

<p style="margin-top: 50px;">Terima kasih atas pembelian Anda.</p>
</body></html>
```

Setelah selesai, test:
- Login owner → buka /order → 50 order (dari seeder) tampil
- Filter date_from/date_to → query lengkap
- Klik order → detail
- Cetak struk → window print dialog
- Klik PDF → download file `.pdf`
- Export Excel → download `.xlsx`
````

## Hasil yang Diharapkan

```
app/
├── Http/Controllers/OrderController.php
└── Exports/OrdersExport.php
resources/views/pages/orders/
├── index.blade.php
├── view.blade.php
├── receipt.blade.php
└── invoice-pdf.blade.php
```

## Cara Test

```bash
# Lihat di prompt section atas
```

## Penjelasan untuk Murid

Talking points:

1. **"Filter berlapis — pakai conditional `when` atau if-statement?"** — Pakai `if ($request->filled(...))`. `when` ringkas tapi sulit untuk filter kompleks (date range butuh 2 if).

2. **"Kasir hanya lihat order sendiri — di Controller bukan Policy?"** — Policy untuk single record (`view`). Untuk filter LIST query, di Controller. Sinkron, supaya tidak ada leak: list filter + policy guard double-protection.

3. **"`clone $query->getQuery()`?"** — Hindari mutate query asli. Penting kalau setelah aggregate masih mau pakai query untuk paginate.

4. **"Receipt thermal CSS `@page size: 80mm auto`?"** — Native CSS Paged Media. Browser kalau print akan match size kalau printer support roll paper.

5. **"`Pdf::loadView()->setPaper('A4')`?"** — Render Blade ke HTML → DomPDF parse → output PDF. Limit: tidak semua CSS modern di-support (no flexbox sebelum DomPDF 3.x).

6. **"`Excel::download(new Export, 'file.xlsx')`?"** — Maatwebsite handle stream + download header. Class Export implement contracts (`FromQuery`, `WithHeadings`, dll).

7. **"`withQueryString()` di paginator?"** — Preserve query params (filter, sort) saat navigate halaman. Tanpa ini, klik page 2 → filter hilang.

Pertanyaan reflektif:
- "Kalau struk butuh QR code untuk e-faktur?" (→ `simplesoftwareio/simple-qrcode`, generate base64 di view)
- "Bagaimana kirim invoice via email otomatis setelah order selesai?" (→ Mailable + Event `OrderCreated`, queue background)

---

**Next: [16-cash-sessions.md](16-cash-sessions.md)**
