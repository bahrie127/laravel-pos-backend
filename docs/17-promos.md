# Step 17 — Promo System (Persen / Rupiah / B1G1)

## Tujuan

CRUD Promo + apply ke order:
- 3 tipe: percent (10% off), rupiah (Rp 5.000 off), b1g1 (buy 1 get 1).
- Schedule (starts_at / ends_at).
- Code voucher (optional).
- Min subtotal threshold.
- Endpoint API `apply` untuk validasi code dari Flutter.

## Prasyarat

- Step 16 selesai

## Konteks

Promo = booster sales. Schema sudah ada dari Step 07. Sekarang controller + view + apply logic.

## Prompt untuk AI

````
Project Laravel POS sudah punya Cash Session. Sekarang buat Promo system.

A. ROUTE

1. `routes/web.php`:
```php
Route::post('promo/{promo}/toggle', [\App\Http\Controllers\PromoController::class, 'toggle'])->name('promo.toggle');
Route::resource('promo', \App\Http\Controllers\PromoController::class)->except(['show']);
```

B. CONTROLLER (Web)

2. `app/Http/Controllers/PromoController.php`:
```php
namespace App\Http\Controllers;

use App\Models\Promo;
use App\Http\Requests\PromoRequest;
use Illuminate\Http\Request;

class PromoController extends Controller {
    public function __construct() {
        $this->authorizeResource(Promo::class, 'promo');
    }

    public function index(Request $request) {
        $query = Promo::withCount('orders');
        if ($request->filled('q')) $query->where('name','like','%'.$request->q.'%');
        if ($request->filled('status')) {
            match($request->status) {
                'live' => $query->live(),
                'inactive' => $query->where('active', false),
                'scheduled' => $query->where('active',true)->where('starts_at','>',now()),
                'expired' => $query->where('ends_at','<',now()),
                default => null,
            };
        }
        $promos = $query->latest()->paginate(15)->withQueryString();
        return view('pages.promos.index', compact('promos'));
    }

    public function create() {
        return view('pages.promos.create', ['promo' => new Promo(['type' => 'percent', 'active' => true])]);
    }

    public function store(PromoRequest $request) {
        $data = $request->validated();
        if (! empty($data['code'])) $data['code'] = strtoupper($data['code']);
        Promo::create($data);
        return redirect()->route('promo.index')->with('success', __('messages.created', ['resource' => 'Promo']));
    }

    public function edit(Promo $promo) {
        return view('pages.promos.edit', compact('promo'));
    }

    public function update(PromoRequest $request, Promo $promo) {
        $data = $request->validated();
        if (! empty($data['code'])) $data['code'] = strtoupper($data['code']);
        $promo->update($data);
        return redirect()->route('promo.index')->with('success', __('messages.updated', ['resource' => 'Promo']));
    }

    public function toggle(Promo $promo) {
        $this->authorize('update', $promo);
        $promo->update(['active' => ! $promo->active]);
        return back()->with('success', __('Status promo diubah.'));
    }

    public function destroy(Promo $promo) {
        if ($promo->orders()->exists()) {
            return back()->with('error', __('Promo tidak bisa dihapus karena sudah dipakai di pesanan.'));
        }
        $promo->delete();
        return back()->with('success', __('messages.deleted', ['resource' => 'Promo']));
    }
}
```

C. FORM REQUEST

3. `app/Http/Requests/PromoRequest.php`:
```php
public function authorize(): bool { return true; }
public function rules(): array {
    $id = $this->route('promo')?->id;
    return [
        'name' => ['required','string','max:100'],
        'type' => ['required','in:percent,rupiah,b1g1'],
        'value' => ['required','integer','min:0','max:100000000'],
        'code' => ['nullable','string','max:50','unique:promos,code,'.$id],
        'min_subtotal' => ['nullable','integer','min:0'],
        'starts_at' => ['nullable','date'],
        'ends_at' => ['nullable','date','after_or_equal:starts_at'],
        'active' => ['nullable','boolean'],
    ];
}
public function withValidator($validator): void {
    $validator->after(function ($v) {
        if ($this->type === 'percent' && ($this->value < 1 || $this->value > 100)) {
            $v->errors()->add('value', __('Untuk tipe persen, nilai harus 1-100.'));
        }
    });
}
protected function prepareForValidation(): void {
    $this->merge(['active' => $this->boolean('active', true)]);
}
```

D. API CONTROLLER

4. `app/Http/Controllers/Api/PromoController.php`:
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromoResource;
use App\Http\Responses\ApiResponse;
use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller {
    public function index() {
        $promos = Promo::live()->orderBy('name')->get();
        return ApiResponse::success(PromoResource::collection($promos));
    }

    public function show(Promo $promo) {
        return ApiResponse::success(new PromoResource($promo));
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'type' => ['required','in:percent,rupiah,b1g1'],
            'value' => ['required','integer','min:0'],
            'code' => ['nullable','string','max:50','unique:promos,code'],
            'min_subtotal' => ['nullable','integer','min:0'],
        ]);
        if (! empty($data['code'])) $data['code'] = strtoupper($data['code']);
        $promo = Promo::create($data);
        return ApiResponse::success(new PromoResource($promo), 'Promo dibuat.', 201);
    }

    public function update(Request $request, Promo $promo) {
        $data = $request->validate([
            'name' => ['sometimes','string','max:100'],
            'value' => ['sometimes','integer','min:0'],
            'active' => ['sometimes','boolean'],
        ]);
        $promo->update($data);
        return ApiResponse::success(new PromoResource($promo));
    }

    public function destroy(Promo $promo) {
        if ($promo->orders()->exists()) return ApiResponse::error('Promo sudah dipakai.', 422);
        $promo->delete();
        return ApiResponse::success(null, 'Promo dihapus.');
    }

    public function toggle(Promo $promo) {
        $promo->update(['active' => ! $promo->active]);
        return ApiResponse::success(new PromoResource($promo));
    }

    public function apply(Request $request) {
        $request->validate([
            'code' => ['required','string'],
            'subtotal' => ['required','integer','min:0'],
            'items' => ['nullable','array'],
        ]);
        $promo = Promo::byCode($request->code)->first();
        if (! $promo) return ApiResponse::error('Kode promo tidak ditemukan.', 404);
        if (! $promo->isLive()) return ApiResponse::error('Promo tidak aktif atau sudah berakhir.', 422);
        if ($request->subtotal < $promo->min_subtotal) {
            return ApiResponse::error(__('Minimum belanja Rp :min', ['min' => number_format($promo->min_subtotal, 0, ',', '.')]), 422);
        }
        $discount = $promo->computeDiscount((int) $request->subtotal, $request->items ?? []);
        return ApiResponse::success([
            'promo' => new PromoResource($promo),
            'discount' => $discount,
        ], 'Promo berhasil diterapkan.');
    }
}
```

5. `app/Http/Resources/PromoResource.php`:
```php
return [
    'id' => $this->id,
    'name' => $this->name,
    'type' => $this->type,
    'type_label' => $this->typeLabel(),
    'value' => $this->value,
    'code' => $this->code,
    'min_subtotal' => $this->min_subtotal,
    'starts_at' => $this->starts_at?->toIso8601String(),
    'ends_at' => $this->ends_at?->toIso8601String(),
    'active' => $this->active,
    'status' => $this->status(),
];
```

6. Route `routes/api.php`:
```php
Route::prefix('promos')->group(function () {
    Route::get('/', [PromoController::class, 'index']);
    Route::post('/', [PromoController::class, 'store']);
    Route::post('apply', [PromoController::class, 'apply']);
    Route::get('{promo}', [PromoController::class, 'show']);
    Route::put('{promo}', [PromoController::class, 'update']);
    Route::post('{promo}/toggle', [PromoController::class, 'toggle']);
    Route::delete('{promo}', [PromoController::class, 'destroy']);
});
```

E. VIEWS

7. `pages/promos/index.blade.php`:
```blade
@extends('layouts.app')
@section('title', __('Promo'))
@section('main')
<section class="section">
    <x-page-header title="{{ __('Promo') }}">
        <x-slot:actions>
            @can('create', App\Models\Promo::class)
                <a href="{{ route('promo.create') }}" class="btn btn-primary"><i class="fas fa-plus me-2"></i>{{ __('Tambah Promo') }}</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card-clean mb-3">
        <form method="GET" class="row g-2">
            <div class="col-md-5"><input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Cari nama promo...') }}" class="form-control"></div>
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value="">{{ __('Semua Status') }}</option>
                    <option value="live" @selected(request('status')==='live')>{{ __('Live') }}</option>
                    <option value="scheduled" @selected(request('status')==='scheduled')>{{ __('Terjadwal') }}</option>
                    <option value="expired" @selected(request('status')==='expired')>{{ __('Kadaluarsa') }}</option>
                    <option value="inactive" @selected(request('status')==='inactive')>{{ __('Nonaktif') }}</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('Filter') }}</button></div>
            <div class="col-md-2"><a href="{{ route('promo.index') }}" class="btn btn-light w-100">{{ __('Reset') }}</a></div>
        </form>
    </div>

    <div class="card-clean">
        @if($promos->isEmpty())
            <x-empty-state icon="percent" title="{{ __('Belum ada promo') }}"/>
        @else
            <table class="table align-middle">
                <thead class="text-uppercase small text-muted">
                    <tr><th>{{ __('Nama') }}</th><th>{{ __('Kode') }}</th><th>{{ __('Tipe') }}</th><th class="text-end">{{ __('Nilai') }}</th><th>{{ __('Periode') }}</th><th class="text-center">{{ __('Status') }}</th><th class="text-center">{{ __('Pakai') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($promos as $p)
                        <tr>
                            <td><strong>{{ $p->name }}</strong></td>
                            <td>@if($p->code)<code>{{ $p->code }}</code>@else — @endif</td>
                            <td>{{ $p->typeLabel() }}</td>
                            <td class="text-end">
                                @if($p->type === 'percent') {{ $p->value }}%
                                @elseif($p->type === 'rupiah') {{ rupiah($p->value) }}
                                @else B1G1
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $p->starts_at ? formatDate($p->starts_at, 'd M Y') : '—' }} →
                                {{ $p->ends_at ? formatDate($p->ends_at, 'd M Y') : __('Tanpa batas') }}
                            </td>
                            <td class="text-center">
                                @php $st = $p->status(); @endphp
                                @if($st === 'live')<span class="badge bg-success">{{ __('Live') }}</span>
                                @elseif($st === 'scheduled')<span class="badge bg-info">{{ __('Terjadwal') }}</span>
                                @elseif($st === 'expired')<span class="badge bg-secondary">{{ __('Kadaluarsa') }}</span>
                                @else<span class="badge bg-dark">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $p->orders_count }}</td>
                            <td>
                                <form action="{{ route('promo.toggle', $p) }}" method="POST" class="d-inline">@csrf
                                    <button class="btn btn-sm btn-light" title="Toggle"><i class="fas fa-power-off"></i></button>
                                </form>
                                @can('update', $p)<a href="{{ route('promo.edit', $p) }}" class="btn btn-sm btn-light"><i class="fas fa-pencil-alt"></i></a>@endcan
                                @can('delete', $p)<button class="btn btn-sm btn-light text-danger confirm-delete" data-action="{{ route('promo.destroy', $p) }}"><i class="fas fa-trash"></i></button>@endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $promos->links() }}</div>
        @endif
    </div>
</section>
@endsection
```

8. `pages/promos/_form.blade.php`:
```blade
@php $isEdit = $promo->exists; @endphp
<form action="{{ $isEdit ? route('promo.update', $promo) : route('promo.store') }}" method="POST">
    @csrf @if($isEdit) @method('PUT') @endif
    <div class="card-clean">
        <h5 class="mb-3">{{ __('Informasi Promo') }}</h5>
        <x-form-input name="name" label="Nama Promo" :value="$promo->name" required/>
        <div class="row">
            <div class="col-md-4">
                <x-form-select name="type" label="Tipe" :options="['percent'=>'Persen','rupiah'=>'Rupiah','b1g1'=>'Beli 1 Gratis 1']" :value="$promo->type" required/>
            </div>
            <div class="col-md-4">
                <x-form-input name="value" type="number" label="Nilai" :value="$promo->value" required help="{{ __('Untuk percent: 1-100. Untuk rupiah: nominal. Untuk b1g1: kosongkan/0.') }}"/>
            </div>
            <div class="col-md-4">
                <x-form-input name="min_subtotal" type="number" label="Minimal Belanja" :value="$promo->min_subtotal"/>
            </div>
        </div>
        <x-form-input name="code" label="Kode Voucher (opsional)" :value="$promo->code" help="{{ __('Kode akan dipakai customer saat checkout.') }}"/>

        <div class="row">
            <div class="col-md-6"><x-form-input name="starts_at" type="datetime-local" label="Mulai" :value="$promo->starts_at?->format('Y-m-d\TH:i')"/></div>
            <div class="col-md-6"><x-form-input name="ends_at" type="datetime-local" label="Berakhir" :value="$promo->ends_at?->format('Y-m-d\TH:i')"/></div>
        </div>

        <x-form-toggle name="active" label="Aktif" :checked="$promo->active ?? true"/>
        <button class="btn btn-primary">{{ __('Simpan') }}</button>
        <a href="{{ route('promo.index') }}" class="btn btn-light">{{ __('Batal') }}</a>
    </div>
</form>
```

9. `create.blade.php` + `edit.blade.php` extends + include form.

F. INTEGRASI ORDER

10. Saat create order (API) terima `promo_id` atau `promo_code`, hitung `discount_amount`, simpan ke `orders.promo_id` + `orders.discount`. Detail di Step 19 (Mobile API).

Tampilkan struktur folder `pages/promos/` setelah selesai.
````

## Hasil yang Diharapkan

```
app/
├── Http/Controllers/PromoController.php
├── Http/Controllers/Api/PromoController.php
├── Http/Requests/PromoRequest.php
└── Http/Resources/PromoResource.php
resources/views/pages/promos/{index, create, edit, _form}.blade.php
routes/web.php / routes/api.php
```

## Cara Test

```bash
# Web
# 1. /promo → kosong → klik Tambah
# 2. Nama "Diskon Weekend", type percent, value 15, kode "WEEKEND15", starts/ends weekend
# 3. /promo → tampil dengan badge Live/Scheduled
# 4. Klik toggle (power icon) → status berubah → toggle balik
# 5. Hapus promo yang ada order_count > 0 → error

# API
TOKEN="..." # token dari /api/login
# Apply code
curl -X POST http://localhost:8000/api/promos/apply \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"code":"WEEKEND15","subtotal":100000}'
# Expected: discount = 15000

# Invalid code
curl ... -d '{"code":"INVALID","subtotal":100000}'
# Expected: 404 error
```

## Penjelasan untuk Murid

Talking points:

1. **"`computeDiscount()` di Model — kenapa di model bukan service?"** — Logic terkait Promo entity. Reusable dari web + API + cron. Kalau makin kompleks, baru extract ke `PromoDiscountCalculator` class.

2. **"`applies_to` JSON kolom — kenapa?"** — Future-proof: kalau promo cuma untuk produk/kategori tertentu, simpan ID di JSON. Sekarang null = berlaku semua.

3. **"`b1g1` tipe — kenapa value=0?"** — Tidak butuh nilai. Logic-nya: untuk tiap qty >= 2 di items, diskon = harga * floor(qty/2).

4. **"`isLive()` cek 3 hal — kenapa di-extract method?"** — Reusable di scope `live()`, di `apply()` validasi, di `status()` label.

5. **"`scope::byCode()` pakai `whereRaw UPPER`?"** — Case-insensitive match. User input "weekend15" tetap match "WEEKEND15". DB index masih kepakai kalau pakai functional index (advanced).

6. **"Validation `after_or_equal:starts_at`?"** — Cross-field validation. ends_at harus >= starts_at.

Pertanyaan reflektif:
- "Bagaimana implement promo 'beli 3 bayar 2' (BOGO 3 of 2)?" (→ tambah type `b3p2`, logic mirip b1g1 tapi divisor 3)
- "Kalau ada batas pemakaian (max 100 kali pakai), schema-nya?" (→ tambah kolom `max_usage int`, increment counter di Order::created event)

---

**Next: [18-reports.md](18-reports.md)**
