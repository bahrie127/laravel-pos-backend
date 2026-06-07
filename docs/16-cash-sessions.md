# Step 16 — Cash Sessions (Buka/Tutup Shift Kasir)

## Tujuan

Implementasi **manajemen shift kasir**:
- Buka shift dengan modal awal (opening_float).
- Setiap order otomatis di-attach ke shift aktif kasir.
- Tutup shift dengan hitung fisik → server hitung expected & variance.
- Admin bisa force-close shift yang lupa ditutup.

## Prasyarat

- Step 15 selesai

## Konteks

POS profesional butuh tracking duit kasir. Kalau ada selisih (variance > 0) → audit. Tanpa shift, tidak ada cara accountable kalau ada selisih kas.

## Prompt untuk AI

````
Project Laravel POS sudah punya Orders. Sekarang implement Cash Sessions.

A. ROUTE

1. `routes/web.php`:
```php
Route::prefix('cash-sessions')->name('cash-session.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CashSessionController::class, 'index'])->name('index');
    Route::post('/open', [\App\Http\Controllers\CashSessionController::class, 'open'])->name('open');
    Route::get('/{cashSession}', [\App\Http\Controllers\CashSessionController::class, 'show'])->name('show');
    Route::post('/{cashSession}/close', [\App\Http\Controllers\CashSessionController::class, 'close'])->name('close');
    Route::post('/{cashSession}/force-close', [\App\Http\Controllers\CashSessionController::class, 'forceClose'])->name('force-close');
});
```

B. CONTROLLER

2. `app/Http/Controllers/CashSessionController.php`:
```php
namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Order;
use Illuminate\Http\Request;

class CashSessionController extends Controller {
    public function index(Request $request) {
        $query = CashSession::with('user:id,name')->withCount('orders');
        if (! $request->user()->isAdmin()) {
            $query->forUser($request->user()->id);
        }
        if ($request->filled('status')) {
            $request->status === 'open' ? $query->whereNull('closed_at') : $query->whereNotNull('closed_at');
        }
        if ($request->filled('date_from')) $query->whereDate('opened_at','>=',$request->date_from);
        if ($request->filled('date_to')) $query->whereDate('opened_at','<=',$request->date_to);

        $sessions = $query->latest('opened_at')->paginate(20)->withQueryString();
        $mySession = CashSession::currentFor($request->user()->id);

        // Stats
        $stats = [
            'open_now' => CashSession::open()->count(),
            'closed_today' => CashSession::whereDate('closed_at', today())->count(),
            'variance_today' => (int) CashSession::whereDate('closed_at', today())->sum('variance'),
            'cash_revenue_today' => (int) Order::whereDate('transaction_time', today())
                ->where('payment_method','cash')->where('status', Order::STATUS_PAID)->sum('amount_paid'),
        ];

        return view('pages.cash-sessions.index', compact('sessions','mySession','stats'));
    }

    public function open(Request $request) {
        $data = $request->validate([
            'shift_label' => ['required','in:Pagi,Siang,Malam'],
            'opening_float' => ['required','integer','min:0'],
            'opening_note' => ['nullable','string','max:500'],
        ]);
        if (CashSession::open()->forUser($request->user()->id)->exists()) {
            return back()->with('error', __('Anda sudah punya shift aktif.'));
        }
        $session = CashSession::create($data + [
            'user_id' => $request->user()->id,
            'opened_at' => now(),
        ]);
        return redirect()->route('cash-session.show', $session)->with('success', __('Shift dibuka.'));
    }

    public function show(CashSession $cashSession) {
        $this->authorize('view', $cashSession);
        $orders = $cashSession->orders()->with('orderItems')->latest('transaction_time')->get();
        $revenueByMethod = $cashSession->revenueByMethod();
        $cashRevenue = $cashSession->cashRevenue();
        $expectedCash = $cashSession->opening_float + $cashSession->cash_in - $cashSession->cash_out + $cashRevenue;
        return view('pages.cash-sessions.show', compact('cashSession','orders','revenueByMethod','cashRevenue','expectedCash'));
    }

    public function close(Request $request, CashSession $cashSession) {
        $this->authorize('view', $cashSession);
        $data = $request->validate([
            'physical_count' => ['required','integer','min:0'],
            'cash_in' => ['nullable','integer','min:0'],
            'cash_out' => ['nullable','integer','min:0'],
            'closing_note' => ['nullable','string','max:500'],
        ]);
        if ($cashSession->closed_at) return back()->with('error', __('Shift sudah ditutup.'));

        $cashRevenue = $cashSession->cashRevenue();
        $expected = $cashSession->opening_float + ($data['cash_in'] ?? 0) - ($data['cash_out'] ?? 0) + $cashRevenue;
        $variance = $data['physical_count'] - $expected;

        $cashSession->update($data + [
            'expected_cash' => $expected,
            'variance' => $variance,
            'closed_at' => now(),
        ]);
        return redirect()->route('cash-session.show', $cashSession)
            ->with('success', $variance === 0 ? __('Shift ditutup. Saldo balanced.') : __('Shift ditutup. Selisih: :v', ['v' => rupiah($variance)]));
    }

    public function forceClose(Request $request, CashSession $cashSession) {
        $this->authorize('forceClose', $cashSession);
        $cashRevenue = $cashSession->cashRevenue();
        $expected = $cashSession->opening_float + $cashSession->cash_in - $cashSession->cash_out + $cashRevenue;
        $cashSession->update([
            'physical_count' => $expected, // assume balanced
            'expected_cash' => $expected,
            'variance' => 0,
            'closing_note' => '[Force-closed oleh '.$request->user()->name.']',
            'closed_at' => now(),
        ]);
        return back()->with('success', __('Shift di-force close.'));
    }
}
```

C. ATTACH ORDER KE SHIFT (modify Order creation)

3. Saat order dibuat (API atau Web), auto-attach `cash_session_id`. Edit `Order` model (boot):
```php
static::creating(function ($order) {
    if (empty($order->order_number)) $order->order_number = self::generateOrderNumber();
    if (empty($order->status)) $order->status = self::STATUS_PAID;
    if (empty($order->cash_session_id) && $order->kasir_id) {
        $current = CashSession::currentFor($order->kasir_id);
        if ($current) $order->cash_session_id = $current->id;
    }
});
```

D. VIEW

4. `pages/cash-sessions/index.blade.php`:
```blade
@extends('layouts.app')
@section('title', __('Cash Session'))
@section('main')
<section class="section">
    <x-page-header title="{{ __('Cash Session') }}">
        <x-slot:actions>
            @if(! $mySession)
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#openShiftModal"><i class="fas fa-key me-2"></i>{{ __('Buka Shift') }}</button>
            @else
                <a href="{{ route('cash-session.show', $mySession) }}" class="btn btn-success"><i class="fas fa-clock me-2"></i>{{ __('Shift Aktif Anda') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="row mb-3">
        <div class="col-md-3"><x-stat-card label="{{ __('Sedang Buka') }}" value="{{ $stats['open_now'] }}" icon="door-open" color="primary"/></div>
        <div class="col-md-3"><x-stat-card label="{{ __('Tutup Hari Ini') }}" value="{{ $stats['closed_today'] }}" icon="check" color="success"/></div>
        <div class="col-md-3"><x-stat-card label="{{ __('Variance Hari Ini') }}" value="{{ rupiah($stats['variance_today']) }}" icon="balance-scale" color="warning"/></div>
        <div class="col-md-3"><x-stat-card label="{{ __('Pendapatan Tunai') }}" value="{{ rupiah($stats['cash_revenue_today']) }}" icon="money-bill-wave" color="info"/></div>
    </div>

    <div class="card-clean mb-3">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value="">{{ __('Semua') }}</option>
                    <option value="open" @selected(request('status')==='open')>{{ __('Buka') }}</option>
                    <option value="closed" @selected(request('status')==='closed')>{{ __('Tutup') }}</option>
                </select>
            </div>
            <div class="col-md-3"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
            <div class="col-md-3"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100">{{ __('Filter') }}</button></div>
        </form>
    </div>

    <div class="card-clean">
        @if($sessions->isEmpty())
            <x-empty-state icon="cash-register" title="{{ __('Belum ada shift') }}"/>
        @else
            <table class="table align-middle">
                <thead class="text-uppercase small text-muted">
                    <tr><th>{{ __('Kasir') }}</th><th>{{ __('Shift') }}</th><th>{{ __('Buka') }}</th><th>{{ __('Tutup') }}</th><th class="text-end">{{ __('Modal') }}</th><th class="text-end">{{ __('Variance') }}</th><th class="text-center">{{ __('Order') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($sessions as $s)
                        <tr>
                            <td>{{ $s->user->name ?? '-' }}</td>
                            <td><span class="badge bg-light text-dark">{{ $s->shift_label }}</span></td>
                            <td>{{ formatDate($s->opened_at) }}</td>
                            <td>{{ $s->closed_at ? formatDate($s->closed_at) : '<span class="badge bg-warning">'.__('Buka').'</span>' }}</td>
                            <td class="text-end">{{ rupiah($s->opening_float) }}</td>
                            <td class="text-end">
                                @if(! is_null($s->variance))
                                    <span class="text-{{ $s->variance === 0 ? 'success' : ($s->variance < 0 ? 'danger' : 'warning') }}">{{ rupiah($s->variance) }}</span>
                                @else — @endif
                            </td>
                            <td class="text-center">{{ $s->orders_count }}</td>
                            <td>
                                <a href="{{ route('cash-session.show', $s) }}" class="btn btn-sm btn-light"><i class="fas fa-eye"></i></a>
                                @if($s->is_open && auth()->user()->isAdmin() && $s->user_id !== auth()->id())
                                    <form action="{{ route('cash-session.force-close', $s) }}" method="POST" class="d-inline">@csrf
                                        <button class="btn btn-sm btn-light text-danger" onclick="return confirm('Force close shift?')">{{ __('Force Close') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $sessions->links() }}</div>
        @endif
    </div>

    {{-- Modal buka shift --}}
    <x-modal id="openShiftModal" title="{{ __('Buka Shift') }}">
        <form action="{{ route('cash-session.open') }}" method="POST">@csrf
            <x-form-select name="shift_label" label="Shift" :options="['Pagi'=>'Pagi','Siang'=>'Siang','Malam'=>'Malam']" required/>
            <x-form-input name="opening_float" label="Modal Awal" type="number" required help="Jumlah uang fisik di laci kasir."/>
            <x-form-textarea name="opening_note" label="Catatan" rows="2"/>
            <button class="btn btn-primary">{{ __('Buka Shift') }}</button>
        </form>
    </x-modal>
</section>
@endsection
```

5. `pages/cash-sessions/show.blade.php` (detail + close form):
```blade
@extends('layouts.app')
@section('title', __('Shift :id', ['id' => $cashSession->id]))
@section('main')
<section class="section">
    <x-page-header
        title="{{ __('Shift :label - :kasir', ['label' => $cashSession->shift_label, 'kasir' => $cashSession->user->name]) }}"
        subtitle="{{ __('Dibuka :time', ['time' => formatDate($cashSession->opened_at)]) }}"
        :breadcrumbs="[['label'=>__('Cash Session'),'url'=>route('cash-session.index')], ['label'=>$cashSession->shift_label]]"/>

    <div class="row">
        <div class="col-lg-7">
            <div class="card-clean mb-3">
                <h5 class="mb-3">{{ __('Pendapatan per Metode') }}</h5>
                <table class="table">
                    @foreach($revenueByMethod as $method => $total)
                        <tr><td>{{ strtoupper($method) }}</td><td class="text-end fw-medium">{{ rupiah($total) }}</td></tr>
                    @endforeach
                </table>
            </div>
            <div class="card-clean">
                <h5 class="mb-3">{{ __(':n Pesanan di Shift Ini', ['n' => $orders->count()]) }}</h5>
                <table class="table align-middle">
                    @foreach($orders as $o)
                        <tr>
                            <td><a href="{{ route('order.show', $o) }}" class="text-primary">{{ $o->order_number }}</a></td>
                            <td>{{ formatDate($o->transaction_time, 'H:i') }}</td>
                            <td class="text-end">{{ rupiah($o->total_price) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card-clean">
                <h5 class="mb-3">{{ $cashSession->is_open ? __('Tutup Shift') : __('Ringkasan Tutup') }}</h5>
                <dl class="row">
                    <dt class="col-7 text-muted">{{ __('Modal Awal') }}</dt><dd class="col-5 text-end">{{ rupiah($cashSession->opening_float) }}</dd>
                    <dt class="col-7 text-muted">{{ __('+ Pendapatan Tunai') }}</dt><dd class="col-5 text-end">{{ rupiah($cashRevenue) }}</dd>
                    <dt class="col-7 text-muted">{{ __('+ Cash In') }}</dt><dd class="col-5 text-end">{{ rupiah($cashSession->cash_in) }}</dd>
                    <dt class="col-7 text-muted">{{ __('- Cash Out') }}</dt><dd class="col-5 text-end">-{{ rupiah($cashSession->cash_out) }}</dd>
                    <dt class="col-7 fw-bold border-top pt-2">{{ __('Expected') }}</dt><dd class="col-5 text-end fw-bold border-top pt-2">{{ rupiah($expectedCash) }}</dd>
                </dl>

                @if($cashSession->is_open && ($cashSession->user_id === auth()->id() || auth()->user()->isAdmin()))
                    <hr>
                    <form action="{{ route('cash-session.close', $cashSession) }}" method="POST">@csrf
                        <x-form-input name="cash_in" type="number" label="Cash In Tambahan" value="0"/>
                        <x-form-input name="cash_out" type="number" label="Cash Out Tambahan" value="0"/>
                        <x-form-input name="physical_count" type="number" label="Hitung Fisik" required help="Jumlah uang tunai aktual di laci."/>
                        <x-form-textarea name="closing_note" label="Catatan" rows="2"/>
                        <button class="btn btn-danger w-100"><i class="fas fa-lock me-2"></i>{{ __('Tutup Shift') }}</button>
                    </form>
                @else
                    <dl class="row mt-3">
                        <dt class="col-7 text-muted">{{ __('Hitung Fisik') }}</dt><dd class="col-5 text-end">{{ rupiah($cashSession->physical_count) }}</dd>
                        <dt class="col-7 fw-bold">{{ __('Variance') }}</dt>
                        <dd class="col-5 text-end fw-bold text-{{ $cashSession->variance === 0 ? 'success' : 'danger' }}">{{ rupiah($cashSession->variance) }}</dd>
                    </dl>
                    @if($cashSession->closing_note)
                        <p class="small text-muted mt-2">{{ $cashSession->closing_note }}</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
```

Tampilkan struktur folder `pages/cash-sessions/` setelah selesai.
````

## Hasil yang Diharapkan

```
app/Http/Controllers/CashSessionController.php
app/Models/Order.php                          ← booted creating auto-attach cash_session_id
resources/views/pages/cash-sessions/
├── index.blade.php
└── show.blade.php
routes/web.php                                ← group cash-session.*
```

## Cara Test

```bash
# 1. /cash-sessions → kosong, tombol "Buka Shift" muncul
# 2. Klik → modal → pilih Pagi, opening_float 500000 → Submit
# 3. Redirect ke /cash-sessions/1 → detail shift, expected = 500000 (belum ada penjualan)
# 4. Coba buka shift lagi → error "Anda sudah punya shift aktif"
# 5. Di tinker, buat order kasir tersebut bayar cash 100000 → balik ke shift, expected naik
# 6. Tutup shift: physical_count 600000 → balanced (variance 0)
# 7. Force close: login admin lain → list ada shift orang lain (open) → tombol "Force Close"
```

## Penjelasan untuk Murid

Talking points:

1. **"Kenapa attach `cash_session_id` di Model boot, bukan Controller?"** — Sentralisasi. Order dari API + Web + seeder semua dapat behavior konsisten. Tidak ada code duplication.

2. **"Variance positif vs negatif — artinya?"**
   - Positif (selisih lebih): laci > expected = kasir terima duit lebih (mungkin lupa kembalikan kembalian).
   - Negatif (selisih kurang): laci < expected = kasir kurang uang (mungkin salah hitung atau ada pencurian).

3. **"Cash In / Cash Out apa contohnya?"**
   - Cash In: owner tambah modal di tengah shift (kalau kembalian habis).
   - Cash Out: kasir bayar supplier dengan duit kas, atau setor sebagian ke owner.

4. **"Server-authoritative calculation?"** — Expected & variance dihitung di server, bukan trust input client. Kasir hanya input `physical_count`, sisanya auto. Anti-manipulasi.

5. **"`forceClose` admin — kenapa di-tag note?"** — Audit trail. Kalau ada masalah belakangan, tahu shift ini ditutup paksa siapa.

6. **"Bisa kasir buka 2 shift sekaligus?"** — Tidak, di-block di `open()` dengan check `currentFor()`. Satu kasir = satu shift aktif.

Pertanyaan reflektif:
- "Bagaimana jika ada penyetoran tunai ke bank di tengah shift?" (→ Cash Out + note "Setor Bank")
- "Bagaimana lap menambahkan kategori pengeluaran (PB Cash Out for: gaji, listrik, dll)?" (→ field `cash_out_category` enum, atau tabel `cash_movements` terpisah)

---

**Next: [17-promos.md](17-promos.md)**
