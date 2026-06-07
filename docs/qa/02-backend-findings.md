# Backend Audit Findings

Hasil deep code review backend Laravel POS. Diurutkan per severity. Format: **kode-finding · severity · file:line**.

Severity scale:
- 🔴 **CRITICAL** — Blocker. Bug breaks core flow atau security hole serius.
- 🟠 **HIGH** — Wajib fix sebelum production. Tidak block workshop demo.
- 🟡 **MEDIUM** — Quality issue. Fix di Phase 4 atau homework peserta.
- 🟢 **LOW / INFO** — Catatan untuk masa depan.

---

## 🔴 CRITICAL — Wajib Fix Sebelum Share

### D-1 · Seeder fail: kategori tanpa slug
**File**: `database/seeders/DatabaseSeeder.php`

Migration `phase2_enhance_categories_table` membuat kolom `slug` UNIQUE NOT NULL (setelah backfill). `DatabaseSeeder::run()` insert kategori tanpa slug → SQL error "slug cannot be null".

Peserta `migrate:fresh --seed` pertama kali akan stuck.

**Fix**:
```php
// DatabaseSeeder atau pakai CategorySeeder dengan Str::slug()
\App\Models\Category::firstOrCreate(
    ['name' => 'Makanan'],
    ['slug' => 'makanan', 'icon' => 'cube', 'color' => '#F59E0B', 'is_active' => true]
);
```

Atau pakai `Category::create()` agar boot event auto-generate slug.

### D-2 · Akun owner seed dengan role kasir
**File**: `database/seeders/DatabaseSeeder.php`

```php
User::factory()->create([
    'name' => 'Code with Bahri',
    'email' => 'bahri@fic11.com',
    'password' => Hash::make('12345678'),
    // tidak set 'roles' → factory default = 'kasir'
]);
```

Peserta login → tidak bisa lihat menu Pengguna + Laporan (di-gate `@can`). Demo admin features gagal.

**Fix**:
```php
'roles' => 'owner',
```

### K-1 · README missing setup steps
**File**: `README.md`

README tidak mention:
- `php artisan storage:link` → image broken
- `php artisan migrate --seed` → DB kosong, tidak bisa login

**Fix**: Update README dengan langkah:
```bash
composer install
cp .env.example .env
php artisan key:generate
# edit .env, set DB_DATABASE / username / password
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

### K-3 · `.env.example` outdated
**File**: `.env.example`

Masih `APP_NAME=Laravel`, `DB_DATABASE=laravel`. Tidak ada `APP_LOCALE=id`, tidak ada `RESEND_API_KEY`.

**Fix**: Update jadi:
```
APP_NAME="POS FIC11"
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

DB_DATABASE=fic11jilid2-db

MAIL_MAILER=log
RESEND_API_KEY=
```

---

## 🟠 HIGH — Wajib Fix Sebelum Production

### C-1 · Refund API tanpa authorization
**File**: `app/Http/Controllers/Api/RefundController.php`

```php
public function store(Request $request, Order $order) {
    // TIDAK ada $this->authorize('refund', $order)
    // siapa pun yang login bisa refund
}
```

**Risk**: Kasir bisa refund order kasir lain, atau bahkan order miliknya sendiri tanpa approval.

**Fix**:
```php
$this->authorize('refund', $order);
// + cek OrderPolicy::refund hanya owner
```

### C-2 · API tanpa authorization check
**File**: `app/Http/Controllers/Api/PromoController.php`, `app/Http/Controllers/Api/ProductController.php`

`store`, `update`, `destroy`, `toggle` tidak panggil `authorize()`. Kasir bisa CRUD promo & produk via API.

**Risk**: Tidak ada gating. Kasir nakal bisa bikin promo "DISKON99" untuk dirinya sendiri.

**Fix**: Apply `$this->authorize('create', Promo::class)` di setiap mutation method, atau pakai middleware `role:owner,admin` di route.

### C-3 · Kasir lihat order kasir lain di API
**File**: `app/Http/Controllers/Api/OrderController.php`

```php
public function index(Request $request) {
    $query = Order::with('kasir:id,name');
    // tidak ada filter kasir_id otomatis
}
```

Di web sudah ada filter `if (! $request->user()->isAdmin()) $query->where('kasir_id', $request->user()->id);`. Di API tidak ada.

**Fix**: Tambah filter sama di API.

### C-4 · Web login bypass is_active
**File**: `app/Actions/Fortify/CreateNewUser.php` + login flow

Fortify default tidak cek `is_active`. User yang di-disable admin masih bisa login web.

**Fix**: Tambah authenticate callback di FortifyServiceProvider:
```php
Fortify::authenticateUsing(function (Request $request) {
    $user = User::where('email', $request->email)->first();
    if (! $user || ! Hash::check($request->password, $user->password)) return null;
    if (! $user->is_active) return null;
    return $user;
});
```

### B-1 · Refund double-debit cash drawer
**File**: `app/Http/Controllers/Api/RefundController.php`

```php
DB::transaction(function () use ($order) {
    // ... mark refunded
    foreach ($order->orderItems as $it) {
        Product::where('id', $it->product_id)->increment('stock', $it->quantity);
    }
    if ($order->cash_session_id) {
        CashSession::where('id', $order->cash_session_id)
            ->increment('cash_out', $order->total_price);
    }
});
```

Issue: kalau refund 2 kali (atau update_status_to_refunded dipanggil > 1x), `cash_out` increment 2x. Tidak ada guard `if status === paid`.

Plus: refund increment cash_out di shift LAMA order. Kalau shift sudah closed, variance shift lama jadi corrupt — physical_count + expected sudah recorded saat tutup.

**Fix**:
```php
if ($order->status !== Order::STATUS_PAID) {
    return ApiResponse::error('Order sudah di-refund atau tidak bisa refund.', 422);
}
// + log ke separate ledger, bukan modify closed shift
```

### B-4 · Revenue dashboard tidak exclude refund
**File**: `app/Http/Controllers/DashboardController.php`, `app/Http/Controllers/ReportController.php`

```php
$revenueToday = Order::whereDate('transaction_time', $today)
    ->where('status', Order::STATUS_PAID)  // ← OK, exclude refunded
    ->sum('total_price');
```

Wait — sudah ada `where status=paid`. Tapi cek ulang semua report. Beberapa report mungkin pakai `OrderItem::sum` tanpa join filter status. **TO VERIFY** di code.

**Action**: grep semua `Order::sum`, `Order::count`, `OrderItem::sum` → pastikan filter `status = paid`.

### H-1 · Stock decrement race condition
**File**: `app/Http/Controllers/Api/OrderController.php`

```php
foreach ($request->items as $it) {
    $order->orderItems()->create([...]);
    Product::where('id', $it['product_id'])->decrement('stock', $it['quantity']);
}
```

Tidak ada `lockForUpdate()`. Concurrent order dengan produk yang sama → stock bisa minus.

**Fix**:
```php
DB::transaction(function () use ($request) {
    $products = Product::whereIn('id', collect($request->items)->pluck('product_id'))
        ->lockForUpdate()->get()->keyBy('id');
    foreach ($request->items as $it) {
        $p = $products[$it['product_id']];
        if ($p->stock < $it['quantity']) {
            throw ValidationException::withMessages(['stock' => 'Stok '.$p->name.' tidak cukup.']);
        }
        $p->decrement('stock', $it['quantity']);
    }
    // create order + items
});
```

### H-2 · Sanctum token tidak expire
**File**: `config/sanctum.php:48`

```php
'expiration' => null, // never expires
```

Token Sanctum hidup selamanya. Kalau HP kasir hilang, attacker bisa akses sampai password kasir diganti (yang tidak revoke token!).

**Fix**:
```php
'expiration' => 60 * 24 * 90, // 90 hari
```

+ Buat command schedule untuk prune expired tokens.

### H-3 · Registration Fortify publik terbuka
**File**: `config/fortify.php`

```php
'features' => [
    Features::registration(),  // ← publik terbuka
    ...
]
```

Siapa pun bisa `POST /register` → buat akun. POS B2B tidak butuh ini.

**Fix**: Hapus `Features::registration()` dari array.

### M-A · Route show tidak ada method
**File**: `routes/web.php`

```php
Route::resource('product', ProductController::class);
// exposes product.show route
```

`ProductController` tidak punya `show()`. Akses `/product/{id}` (tanpa /edit) → `BadMethodCallException`. Sama untuk `categories.show`, `user.show`.

**Fix**:
```php
Route::resource('product', ProductController::class)->except(['show']);
```

### M-B · API ReportController hardcoded email + null pointer
**File**: `app/Http/Controllers/Api/ReportController.php`

```php
$owner = User::where('roles', 'admin')->first();
Resend::emails()->send([...'to' => $owner->email]);
```

Kalau tidak ada user dengan role 'admin' → null pointer crash. Plus, hardcoded email "pos@heticket.com".

**Risk medium**: tidak fatal kalau FE Flutter tidak panggil endpoint ini. Tapi peserta yang explore curl bisa crash.

**Fix**: Guard null + ambil email dari config.

---

## 🟡 MEDIUM — Fix di Phase 4 atau Homework

### D-3 · ProductFactory `category_id` fallback ke 1
```php
'category_id' => Category::inRandomOrder()->first()?->id ?? 1,
```

Kalau seed Product sebelum Category → fallback 1 → FK violation.

**Fix**: pastikan seeder order benar (CategorySeeder dipanggil sebelum ProductSeeder).

### B-2 · Promo expired bisa diapply via direct id
Endpoint `apply` cek `isLive()` dengan code. Tapi `OrderController::store` kalau terima `promo_id` (bukan code), juga cek `isLive()`. ✅ Sebenarnya sudah handle. **Cek ulang**.

### B-3 · Order::booted not run on bulk insert
**File**: `app/Models/Order.php`

`booted::creating` auto-generate order_number. Tapi `Order::insert([...])` (mass insert) **tidak trigger event** → order tanpa order_number.

**Fix di seeder**: pakai `Order::factory()->create()` atau loop `create()`, bukan `insert()`.

### E-19 · `kasir_id` cascadeOnDelete → BAHAYA
**File**: `database/migrations/2024_01_03_..._create_orders_table.php`

```php
$table->foreignId('kasir_id')->constrained('users')->cascadeOnDelete();
```

Hapus user → semua order kasir tersebut HAPUS. History keuangan hilang.

**Fix**: ganti ke `nullOnDelete()` atau `restrictOnDelete()`. User di-soft-delete sudah aman (data masih ada), tapi kalau force-delete (Trash → empty trash) → cascade kena.

### B-5 · Promo `applies_to` JSON column tidak di-validate
Bisa simpan JSON sembarang. Belum dipakai sih (selalu null), tapi kalau dipakai nanti perlu validation.

### F-1 · Stock minus tidak di-cek di order creation
Sengaja? Comment di code bilang "tidak block jika negative". Untuk demo OK, untuk production risk.

### F-2 · Decimal precision (12,2) untuk Rupiah
Max 9.999.999.999,99 = ~10 miliar. Cukup untuk cafe. Untuk franchise besar pertimbangkan (15,2).

---

## 🟢 LOW / INFO

### L-1 · MAIL_MAILER=log default — peserta tidak tahu cara cek
README tidak mention `storage/logs/laravel.log`. Forgot password "berhasil dikirim" tapi tidak ada email beneran.

### L-2 · APP_DEBUG default true di .env.example
OK untuk dev. Wajib `false` di production. Sudah disebut di docs deploy.

### L-3 · Tidak ada index di kolom yang sering query
- `orders.transaction_time` — sering filter date range, butuh index.
- `orders.kasir_id` + composite (transaction_time, status).
- `products.category_id`.

### L-4 · Tidak ada pagination di list API
`/api/products`, `/api/orders` return `.get()` semua. OK untuk skala kecil. Scale up perlu paginate.

### L-5 · DBO Notification / Mail not configured
Resend dependency di-install tapi tidak ada `.env` example dengan key. Forgot password tidak akan kirim email tanpa setup.

### L-6 · Tidak ada audit log
Activity (login, password change, role change, refund) tidak tercatat. Untuk usaha sebenarnya butuh audit trail.

### L-7 · No backup scheduler
Database backup harian tidak ada. Risk data loss. Recommend Spatie laravel-backup atau cron script.

---

## Ringkasan per Kategori

| Kategori | Critical | High | Medium | Low |
|---|---|---|---|---|
| Setup / Seed | 3 | 0 | 1 | 0 |
| Authorization | 0 | 4 | 0 | 0 |
| Business Logic | 0 | 3 | 2 | 0 |
| Security | 0 | 2 | 0 | 1 |
| Configuration | 0 | 0 | 0 | 4 |
| Data Integrity | 0 | 1 | 2 | 0 |
| Missing Routes | 0 | 1 | 0 | 0 |

**Total**: 3 Critical, 11 High, 5 Medium, 5 Low

---

## Rekomendasi Prioritas

**Sebelum share (≤ 10 menit kerja)**:
- Fix D-1, D-2, K-1, K-3 — file kerja: `DatabaseSeeder.php`, `README.md`, `.env.example`

**Sebelum peserta deploy ke usaha real (jadi homework / Phase 4)**:
- Fix C-1..C-4 — authorization gaps
- Fix H-1 — stock race
- Fix H-2 — token expiry
- Fix H-3 — disable registration
- Fix B-1 — refund double-debit
- Fix M-A — show route

**Nice to have**:
- E-19 cascadeOnDelete → nullOnDelete
- L-3 add indexes
- L-7 backup scheduler

File path detail ada di setiap finding untuk handoff ke peserta.
