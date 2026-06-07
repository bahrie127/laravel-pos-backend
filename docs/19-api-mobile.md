# Step 19 — API Mobile (Sanctum untuk Flutter)

## Tujuan

API lengkap untuk Flutter POS app:
- Auth (login, logout, me, delete account).
- Products CRUD (create dari kasir).
- Categories (read-only).
- Orders create dengan stock decrement + cash session auto-attach + promo apply.
- Refund order.
- Cash Sessions (open, close, current, summary).
- Promos (apply code).
- Reports (summary, product-sales, close-cashier).

Semua response pakai `ApiResponse` helper standard + Resource serialization.

## Prasyarat

- Step 18 selesai
- Sanctum sudah setup dari Step 03

## Konteks

API ini dipakai Flutter app di `/Users/bahri/development/fic11/flutter_pos_app`. Penting: schema response konsisten supaya FE tidak break.

## Prompt untuk AI

````
Project Laravel POS sudah punya semua web feature. Sekarang lengkapi API mobile dengan endpoint:

A. ROUTE LENGKAP

1. `routes/api.php`:
```php
use App\Http\Controllers\Api\{AuthController, ProductController, CategoryController, OrderController, ReportController, CashSessionController, PromoController, RefundController};

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::get('privacy', fn () => response()->view('legal.privacy')->header('Content-Type','text/html; charset=UTF-8'));

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::get('user', [AuthController::class, 'me']); // legacy compat
    Route::delete('account', [AuthController::class, 'deleteAccount']);

    // Products
    Route::apiResource('products', ProductController::class)->only(['index','show','store','update']);
    Route::post('products/{product}', [ProductController::class, 'update'])->whereNumber('product'); // multipart workaround

    // Orders
    Route::apiResource('orders', OrderController::class)->only(['index','show','store']);
    Route::get('orders/kasir/{kasir_id}', [OrderController::class, 'getByKasirId']);
    Route::post('orders/{order}/refund', [RefundController::class, 'store'])->whereNumber('order');

    // Categories (read-only di API)
    Route::get('list-categories', [CategoryController::class, 'index']);
    Route::apiResource('categories', CategoryController::class)->only(['index']);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('summary', [ReportController::class, 'summary']);
        Route::get('product-sales', [ReportController::class, 'productSales']);
        Route::get('close-cashier', [ReportController::class, 'closeCashier']);
    });

    // Cash Sessions
    Route::prefix('cash-sessions')->group(function () {
        Route::get('/', [CashSessionController::class, 'index']);
        Route::get('current', [CashSessionController::class, 'current']);
        Route::post('open', [CashSessionController::class, 'open']);
        Route::get('{id}', [CashSessionController::class, 'show'])->whereNumber('id');
        Route::get('{id}/summary', [CashSessionController::class, 'summary'])->whereNumber('id');
        Route::post('{id}/close', [CashSessionController::class, 'close'])->whereNumber('id');
    });

    // Promos
    Route::prefix('promos')->group(function () {
        Route::get('/', [PromoController::class, 'index']);
        Route::post('/', [PromoController::class, 'store']);
        Route::post('apply', [PromoController::class, 'apply']);
        Route::get('{promo}', [PromoController::class, 'show']);
        Route::put('{promo}', [PromoController::class, 'update']);
        Route::post('{promo}/toggle', [PromoController::class, 'toggle']);
        Route::delete('{promo}', [PromoController::class, 'destroy']);
    });
});
```

B. RESOURCES

2. `app/Http/Resources/ProductResource.php`:
```php
return [
    'id' => $this->id,
    'name' => $this->name,
    'description' => $this->description,
    'price' => (int) $this->price,
    'stock' => (int) $this->stock,
    'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
    'category_id' => $this->category_id,
    'image' => $this->image ? asset('storage/products/'.$this->image) : null,
    'is_best_seller' => (bool) $this->is_best_seller,
];
```

3. `CategoryResource.php`:
```php
return [
    'id' => $this->id,
    'name' => $this->name,
    'slug' => $this->slug,
    'icon' => $this->icon,
    'color' => $this->color,
    'products_count' => $this->whenCounted('products'),
];
```

4. `OrderResource.php`:
```php
return [
    'id' => $this->id,
    'order_number' => $this->order_number,
    'transaction_time' => $this->transaction_time?->toIso8601String(),
    'kasir' => new UserResource($this->whenLoaded('kasir')),
    'cash_session_id' => $this->cash_session_id,
    'promo_id' => $this->promo_id,
    'payment_method' => $this->payment_method,
    'status' => $this->status,
    'subtotal' => (int) $this->subtotal,
    'discount' => (int) $this->discount,
    'discount_amount' => (int) $this->discount_amount,
    'tax' => (int) $this->tax,
    'total_price' => (int) $this->total_price,
    'amount_paid' => (int) $this->amount_paid,
    'change_amount' => (int) $this->change_amount,
    'total_item' => $this->total_item,
    'customer_name' => $this->customer_name,
    'notes' => $this->notes,
    'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
];
```

5. `OrderItemResource.php`:
```php
return [
    'id' => $this->id,
    'product' => new ProductResource($this->whenLoaded('product')),
    'product_id' => $this->product_id,
    'quantity' => (int) $this->quantity,
    'total_price' => (int) $this->total_price,
];
```

6. `CashSessionResource.php`:
```php
return [
    'id' => $this->id,
    'user' => new UserResource($this->whenLoaded('user')),
    'shift_label' => $this->shift_label,
    'opening_float' => (int) $this->opening_float,
    'opened_at' => $this->opened_at?->toIso8601String(),
    'cash_in' => (int) $this->cash_in,
    'cash_out' => (int) $this->cash_out,
    'physical_count' => $this->physical_count,
    'expected_cash' => $this->expected_cash,
    'variance' => $this->variance,
    'closed_at' => $this->closed_at?->toIso8601String(),
    'is_open' => is_null($this->closed_at),
];
```

C. AUTH CONTROLLER (final)

7. `app/Http/Controllers/Api/AuthController.php`:
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Models\CashSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller {
    public function login(LoginRequest $request) {
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Email atau password salah.', 401);
        }
        if (! $user->is_active) return ApiResponse::error('Akun nonaktif.', 403);

        $user->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);
        $token = $user->createToken('mobile')->plainTextToken;

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login berhasil.');
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return ApiResponse::success(null, 'Logout berhasil.');
    }

    public function me(Request $request) {
        return ApiResponse::success(new UserResource($request->user()));
    }

    public function deleteAccount(Request $request) {
        $request->validate([
            'confirmation' => ['required','in:HAPUS AKUN'],
            'password' => ['required','current_password'],
        ]);
        $user = $request->user();
        if ($user->isOwner() && User::where('roles','owner')->where('id','!=',$user->id)->count() === 0) {
            return ApiResponse::error('Tidak bisa menghapus owner terakhir.', 422);
        }
        if (CashSession::open()->forUser($user->id)->exists()) {
            return ApiResponse::error('Tutup shift dulu sebelum hapus akun.', 422);
        }
        DB::transaction(function () use ($user) {
            $user->forceFill([
                'name' => '[akun dihapus]',
                'email' => 'deleted-'.$user->id.'@deleted.local',
                'avatar' => null, 'phone' => null, 'is_active' => false,
            ])->saveQuietly();
            $user->tokens()->delete();
            $user->delete();
        });
        return ApiResponse::success(null, 'Akun dihapus.');
    }
}
```

D. ORDER CONTROLLER (API)

8. `app/Http/Controllers/Api/OrderController.php`:
```php
public function index(Request $request) {
    $query = Order::with('kasir:id,name');
    if ($request->filled('kasir_id')) $query->where('kasir_id',$request->kasir_id);
    if ($request->filled('status')) $query->where('status',$request->status);
    $orders = $query->latest('transaction_time')->paginate(20);
    return OrderResource::collection($orders);
}

public function show(Order $order) {
    return ApiResponse::success(new OrderResource($order->load('kasir','orderItems.product')));
}

public function store(ApiOrderStoreRequest $request) {
    $user = $request->user();
    $session = CashSession::currentFor($user->id);
    if (! $session) return ApiResponse::error('Buka shift dulu sebelum membuat order.', 422);

    $promo = null; $discount = 0;
    if ($request->filled('promo_code')) {
        $promo = Promo::byCode($request->promo_code)->first();
        if ($promo && $promo->isLive() && $request->subtotal >= $promo->min_subtotal) {
            $discount = $promo->computeDiscount((int) $request->subtotal, $request->items);
        }
    } elseif ($request->filled('promo_id')) {
        $promo = Promo::find($request->promo_id);
        if ($promo && $promo->isLive()) {
            $discount = $promo->computeDiscount((int) $request->subtotal, $request->items);
        }
    }

    $totalPrice = max(0, (int)$request->subtotal - $discount + (int)$request->get('tax', 0));

    return DB::transaction(function () use ($request, $session, $promo, $discount, $totalPrice, $user) {
        $order = Order::create([
            'transaction_time' => $request->get('transaction_time', now()),
            'kasir_id' => $user->id,
            'cash_session_id' => $session->id,
            'promo_id' => $promo?->id,
            'subtotal' => $request->subtotal,
            'discount' => $discount,
            'discount_amount' => $discount,
            'tax' => $request->get('tax', 0),
            'total_price' => $totalPrice,
            'amount_paid' => $request->amount_paid,
            'change_amount' => max(0, (int)$request->amount_paid - $totalPrice),
            'total_item' => collect($request->items)->sum('quantity'),
            'payment_method' => $request->payment_method,
            'customer_name' => $request->customer_name,
            'notes' => $request->notes,
        ]);

        foreach ($request->items as $it) {
            $order->orderItems()->create([
                'product_id' => $it['product_id'],
                'quantity' => $it['quantity'],
                'total_price' => $it['total_price'],
            ]);
            Product::where('id', $it['product_id'])->decrement('stock', $it['quantity']);
        }

        return ApiResponse::success(new OrderResource($order->load('orderItems.product','kasir')), 'Order dibuat.', 201);
    });
}

public function getByKasirId($kasirId) {
    $orders = Order::where('kasir_id', $kasirId)->latest('transaction_time')->limit(50)->get();
    return ApiResponse::success(OrderResource::collection($orders));
}
```

E. REFUND CONTROLLER

9. `app/Http/Controllers/Api/RefundController.php`:
```php
public function store(Request $request, Order $order) {
    abort_unless($request->user()->isOwner(), 403);
    if ($order->status !== Order::STATUS_PAID) return ApiResponse::error('Order tidak bisa di-refund.', 422);
    $data = $request->validate([
        'reason' => ['required','string','max:64'],
        'note' => ['nullable','string','max:500'],
    ]);
    DB::transaction(function () use ($order, $data, $request) {
        $order->update([
            'status' => Order::STATUS_REFUNDED,
            'refunded_at' => now(),
            'refund_reason' => $data['reason'],
            'refund_note' => $data['note'] ?? null,
            'refund_amount' => $order->total_price,
            'refunded_by_user_id' => $request->user()->id,
        ]);
        // restore stock
        foreach ($order->orderItems as $it) {
            Product::where('id', $it->product_id)->increment('stock', $it->quantity);
        }
        // reconciliation: increment cash_out di shift order
        if ($order->cash_session_id) {
            CashSession::where('id', $order->cash_session_id)->increment('cash_out', $order->total_price);
        }
    });
    return ApiResponse::success(new OrderResource($order->fresh()->load('orderItems.product','kasir')), 'Order di-refund.');
}
```

F. FORM REQUESTS

10. `app/Http/Requests/Api/LoginRequest.php`:
```php
public function authorize(): bool { return true; }
public function rules(): array { return ['email'=>['required','email'],'password'=>['required','string','min:6']]; }
```

11. `ApiOrderStoreRequest.php`:
```php
public function authorize(): bool { return true; }
public function rules(): array {
    return [
        'items' => ['required','array','min:1'],
        'items.*.product_id' => ['required','exists:products,id'],
        'items.*.quantity' => ['required','integer','min:1'],
        'items.*.total_price' => ['required','integer','min:0'],
        'subtotal' => ['required','integer','min:0'],
        'tax' => ['nullable','integer','min:0'],
        'amount_paid' => ['required','integer','min:0'],
        'payment_method' => ['required','in:cash,qris,transfer'],
        'transaction_time' => ['nullable','date'],
        'promo_id' => ['nullable','exists:promos,id'],
        'promo_code' => ['nullable','string'],
        'customer_name' => ['nullable','string','max:100'],
        'notes' => ['nullable','string','max:500'],
    ];
}
```

G. PRODUCTS API (multipart workaround)

12. `app/Http/Controllers/Api/ProductController.php`:
```php
public function index(Request $request) {
    $query = Product::with('category:id,name');
    if ($request->filled('q')) $query->where('name','like','%'.$request->q.'%');
    if ($request->filled('category_id')) $query->where('category_id', $request->category_id);
    return ProductResource::collection($query->paginate(50));
}

public function show(Product $product) {
    return ApiResponse::success(new ProductResource($product->load('category')));
}

public function store(ApiProductStoreRequest $request) {
    $data = $request->validated();
    if ($request->hasFile('image')) {
        $data['image'] = basename($request->file('image')->store('products','public'));
    }
    $data['category'] = Category::find($data['category_id'])->name ?? 'food';
    $product = Product::create($data);
    return ApiResponse::success(new ProductResource($product), 'Produk dibuat.', 201);
}

public function update(ApiProductUpdateRequest $request, Product $product) {
    $data = $request->validated();
    if ($request->hasFile('image')) {
        if ($product->image) Storage::disk('public')->delete('products/'.$product->image);
        $data['image'] = basename($request->file('image')->store('products','public'));
    }
    $product->update($data);
    return ApiResponse::success(new ProductResource($product->fresh()));
}
```

H. CATEGORIES (API)

13. `app/Http/Controllers/Api/CategoryController.php`:
```php
public function index() {
    $cats = Category::withCount('products')->where('is_active', true)
        ->orderBy('sort_order')->orderBy('name')->get();
    return ApiResponse::success(CategoryResource::collection($cats));
}
```

I. EXCEPTION HANDLER

14. Edit `bootstrap/app.php` (Laravel 11) atau `app/Exceptions/Handler.php` (Laravel 10):
```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
        if ($request->is('api/*')) return \App\Http\Responses\ApiResponse::error('Validasi gagal', 422, $e->errors());
    });
    $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
        if ($request->is('api/*')) return \App\Http\Responses\ApiResponse::error('Unauthenticated', 401);
    });
    $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
        if ($request->is('api/*')) return \App\Http\Responses\ApiResponse::error('Resource tidak ditemukan', 404);
    });
});
```

Setelah selesai, jalankan:
```
php artisan route:list --columns=method,uri,name | grep api
```

Test dengan Postman atau curl untuk semua endpoint utama.
````

## Hasil yang Diharapkan

```
app/Http/
├── Controllers/Api/
│   ├── AuthController.php
│   ├── CashSessionController.php
│   ├── CategoryController.php
│   ├── OrderController.php
│   ├── ProductController.php
│   ├── PromoController.php
│   ├── RefundController.php
│   └── ReportController.php
├── Requests/Api/
│   ├── LoginRequest.php
│   ├── ApiOrderStoreRequest.php
│   ├── ApiProductStoreRequest.php
│   ├── ApiProductUpdateRequest.php
│   ├── CashSessionOpenRequest.php
│   └── CashSessionCloseRequest.php
└── Resources/
    ├── UserResource.php
    ├── ProductResource.php
    ├── CategoryResource.php
    ├── OrderResource.php
    ├── OrderItemResource.php
    ├── CashSessionResource.php
    └── PromoResource.php
```

## Cara Test

```bash
# Setup
TOKEN=""
LOGIN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"bahri@fic11.com","password":"12345678"}')
TOKEN=$(echo $LOGIN | jq -r '.data.token')

# Open shift
curl -X POST http://localhost:8000/api/cash-sessions/open \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"shift_label":"Pagi","opening_float":500000}'

# List products
curl http://localhost:8000/api/products -H "Authorization: Bearer $TOKEN"

# Create order
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{
    "items":[{"product_id":1,"quantity":2,"total_price":30000}],
    "subtotal":30000,
    "amount_paid":50000,
    "payment_method":"cash"
  }'
# Expected: order created, stock decremented, cash_session_id auto-attached

# Apply promo
curl -X POST http://localhost:8000/api/promos/apply \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"code":"WEEKEND15","subtotal":100000}'

# Refund order (owner only)
curl -X POST http://localhost:8000/api/orders/1/refund \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"reason":"barang rusak","note":"kemasan sobek"}'

# Logout
curl -X POST http://localhost:8000/api/logout -H "Authorization: Bearer $TOKEN"
```

## Penjelasan untuk Murid

Talking points:

1. **"`POST products/{id}` workaround untuk multipart update?"** — HTML form tidak support PUT/PATCH multipart. Flutter `http` package juga tricky. Pakai POST + `_method=PUT` field, atau dedicated POST route. Sudah handle dengan `whereNumber`.

2. **"DB Transaction di create order?"** — Atomic: order + items + stock decrement = satu unit. Kalau salah satu gagal, rollback semua. Hindari stok hilang tapi order tidak ada.

3. **"Promo apply via code vs id?"** — Code untuk customer-driven (mereka punya voucher), id untuk admin-pushed (otomatis applied). Server-authoritative: tetap re-validate `isLive()` + `min_subtotal` walau client kirim discount.

4. **"Refund increment cash_out?"** — Reconciliation: uang yang sebelumnya masuk via order, sekarang keluar via refund. Variance shift tetap balanced.

5. **"Authentication exception handler API-aware?"** — Tanpa override, Laravel render HTML login page → API client confused. Override return JSON 401.

6. **"`ProductResource::collection($products)` di paginate?"** — Auto-build pagination meta + data array. Response include `links` + `meta` standard.

7. **"`whenLoaded()` di Resource?"** — Conditional include. Kalau `with('product')` tidak dipanggil, field `product` tidak muncul (skip N+1).

8. **"Token Sanctum tidak expire by default?"** — Set di `config/sanctum.php` `expiration => 60*24*30` untuk 30 hari. Atau token-specific dengan `createToken('name', ['ability'], now()->addDays(30))`.

Pertanyaan reflektif:
- "Bagaimana add versioning ke API (/api/v1/, /api/v2/)?" (→ prefix group `Route::prefix('v1')`, controller pisah per versi, Resource bisa diturunkan)
- "Bagaimana handle offline-first Flutter (queue requests, sync saat online)?" (→ idempotency key per request, server dedup berdasarkan key)

---

## Penutup

Selamat! Kamu sudah menyelesaikan tutorial **19 step Laravel POS Backend**. Materi yang dicakup:

- ✅ Setup project + dependency
- ✅ Auth dual-mode (Fortify web + Sanctum mobile)
- ✅ Layout admin lengkap (Stisla theme)
- ✅ 22 komponen Blade reusable
- ✅ Helper rupiah/date + i18n Bahasa Indonesia
- ✅ Schema POS lengkap (7 tabel) + soft delete + refund
- ✅ Seeder & factory siap demo
- ✅ Role-based access (3 role + 6 policy + gate + middleware)
- ✅ Dashboard real-time dengan chart
- ✅ CRUD: Categories, Products, Users, Promos
- ✅ Profile self-service + hapus akun
- ✅ Orders + struk thermal + invoice PDF + export Excel
- ✅ Cash Session (shift management dengan variance)
- ✅ 7 jenis Report + export multi-format
- ✅ API Mobile lengkap untuk Flutter

**Selanjutnya (di luar scope tutorial ini):**
- Phase 3 Polish: dark mode, mobile drawer, micro-interaction
- Phase 4 Hardening: testing (PHPUnit), Telescope, backup, deploy CI/CD
- Migrasi ke Spatie Permission untuk granular RBAC
- Tambah scheduled job: email daily report ke owner

Terima kasih telah mengikuti! 🎉

---

**Kembali ke [README.md](README.md)**
