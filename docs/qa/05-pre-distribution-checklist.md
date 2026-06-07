# Pre-Distribution Checklist

**Estimasi total: ~30 menit kerja** untuk fix yang WAJIB sebelum share ZIP ke peserta.

Tanpa fix ini, peserta yang ikuti README persis akan **stuck di setup pertama**:
- Tidak bisa migrate:fresh --seed (D-1)
- Bisa login tapi tidak lihat menu admin (D-2)
- Image tidak muncul (K-1)
- DB kosong (K-2)
- Config defaults masih Laravel placeholder (K-3)

---

## Quick Fix Script (Otomatis)

Kalau mau cepat, jalankan langkah satu-satu di bawah.

---

## FIX #1 — Seeder kategori tanpa slug (5 menit)

**File**: `database/seeders/DatabaseSeeder.php`

### Sebelum (broken)
```php
DB::table('categories')->insert([
    ['name' => 'Makanan'],
    ['name' => 'Minuman'],
    ['name' => 'snack'],
]);
```

### Sesudah (fixed)
Ganti dengan pakai `Category::firstOrCreate` (akan trigger boot event auto-generate slug):
```php
$categories = [
    ['name' => 'Makanan', 'icon' => 'cube', 'color' => '#F59E0B', 'sort_order' => 0],
    ['name' => 'Minuman', 'icon' => 'coffee', 'color' => '#3B82F6', 'sort_order' => 1],
    ['name' => 'Snack', 'icon' => 'gift', 'color' => '#10B981', 'sort_order' => 2],
    ['name' => 'Dessert', 'icon' => 'cake', 'color' => '#EC4899', 'sort_order' => 3],
];
foreach ($categories as $cat) {
    \App\Models\Category::firstOrCreate(['name' => $cat['name']], $cat + ['is_active' => true]);
}
```

**Test**: `php artisan migrate:fresh --seed` → tidak error.

---

## FIX #2 — Akun bahri di-seed sebagai owner (1 menit)

**File**: `database/seeders/DatabaseSeeder.php`

### Sebelum
```php
User::factory()->create([
    'name' => 'Code with Bahri',
    'email' => 'bahri@fic11.com',
    'password' => Hash::make('12345678'),
]);
```

### Sesudah
```php
User::factory()->create([
    'name' => 'Code with Bahri',
    'email' => 'bahri@fic11.com',
    'password' => Hash::make('12345678'),
    'roles' => 'owner',
    'is_active' => true,
]);
```

**Test**: login `bahri@fic11.com` / `12345678` → sidebar tampil menu Pengguna + Laporan.

---

## FIX #3 — Update README setup steps (5 menit)

**File**: `README.md`

Pastikan ada section "Setup" lengkap:

```markdown
## Setup

```bash
# 1. Clone
git clone <repo>
cd laravel-pos-backend-prejilid2

# 2. Install dependencies
composer install

# 3. Setup .env
cp .env.example .env
php artisan key:generate

# 4. Edit .env — set DB credential:
# DB_DATABASE=fic11jilid2-db
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Buat database di MySQL
mysql -u root -e "CREATE DATABASE \`fic11jilid2-db\`;"

# 6. Migrate + seed
php artisan migrate --seed

# 7. Symlink storage (untuk akses image upload)
php artisan storage:link

# 8. Run server
php artisan serve
# → http://localhost:8000

# Login: bahri@fic11.com / 12345678
```
```

---

## FIX #4 — Update .env.example (3 menit)

**File**: `.env.example`

### Tambahkan / ubah:
```env
APP_NAME="POS FIC11"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fic11jilid2-db
DB_USERNAME=root
DB_PASSWORD=

# Mail (development pakai log)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="no-reply@fic11.com"
MAIL_FROM_NAME="${APP_NAME}"

# Resend (opsional, untuk email production)
RESEND_API_KEY=

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000
```

---

## FIX #5 — Hapus route `show` yang tidak ada method (5 menit)

**File**: `routes/web.php`

### Sebelum (akan crash kalau peserta buka URL `/product/{id}` tanpa /edit)
```php
Route::resource('product', ProductController::class);
Route::resource('user', UserController::class);
Route::resource('categories', CategoryController::class);
```

### Sesudah
```php
Route::resource('product', ProductController::class)->except(['show']);
Route::resource('user', UserController::class)->except(['show']);
Route::resource('categories', CategoryController::class)->except(['show']);
```

---

## FIX #6 (OPSIONAL) — Flutter best seller field name (5 menit)

Kalau Flutter project ikut dishare:

**File**: `lib/data/models/request/product_request_model.dart:29`

### Sebelum
```dart
'isBestSeller': isBestSeller.toString(),
```

### Sesudah
```dart
'is_best_seller': isBestSeller.toString(),
```

**Test**: di app, create produk baru dengan toggle "Best Seller" ON → cek BE column `is_best_seller` = 1.

---

## Verifikasi Final (Smoke Test)

Setelah semua fix, jalankan smoke test ini dari fresh clone:

```bash
# 1. Drop DB
mysql -u root -e "DROP DATABASE IF EXISTS \`fic11jilid2-db\`;"

# 2. Fresh setup
composer install
cp .env.example .env
# Edit .env DB credential
php artisan key:generate
mysql -u root -e "CREATE DATABASE \`fic11jilid2-db\`;"
php artisan migrate --seed
php artisan storage:link

# 3. Cek hasil
php artisan tinker
>>> User::count()       # → 8
>>> Category::count()   # → 4 (with slug)
>>> Product::count()    # → 30
>>> Order::count()      # → 50
>>> User::where('email','bahri@fic11.com')->first()->roles
=> "owner"

# 4. Web smoke
php artisan serve
# Buka http://localhost:8000
# Login bahri@fic11.com / 12345678
# Cek sidebar: Dashboard, Kategori, Produk, Promo, Pengguna, Pesanan, Cash Session, Laporan
# Klik tiap menu → tidak ada 500 error
```

Kalau semua ✅, siap dipack jadi ZIP.

---

## Cara Pack ZIP

```bash
# Dari folder parent project
cd /Users/bahri/development/FIC11Jilid2/

# Exclude folder yang tidak perlu
zip -r laravel-pos-backend-fic11-jilid3.zip laravel-pos-backend-prejilid2/ \
    -x "*/node_modules/*" \
    -x "*/vendor/*" \
    -x "*/.git/*" \
    -x "*/storage/app/public/products/*" \
    -x "*/storage/app/public/avatars/*" \
    -x "*/storage/logs/*" \
    -x "*/storage/framework/cache/*" \
    -x "*/storage/framework/sessions/*" \
    -x "*/storage/framework/views/*" \
    -x "*/.env" \
    -x "*/.phpunit.result.cache" \
    -x "*/public/storage" \
    -x "*/.DS_Store"

# Cek size
ls -lh laravel-pos-backend-fic11-jilid3.zip
# Target: ≤ 20 MB
```

Untuk Flutter:

```bash
cd /Users/bahri/development/fic11/

zip -r flutter-pos-app-fic11-jilid3.zip flutter_pos_app/ \
    -x "*/build/*" \
    -x "*/.dart_tool/*" \
    -x "*/.flutter-plugins*" \
    -x "*/.git/*" \
    -x "*/ios/Pods/*" \
    -x "*/ios/Runner.xcworkspace/*" \
    -x "*/android/.gradle/*" \
    -x "*/android/key.properties" \
    -x "*/android/app/upload-keystore.jks" \
    -x "*/.DS_Store" \
    -x "*/pubspec.lock"
```

---

## Setelah Fix — Estimasi Waktu

| Step | Waktu |
|---|---|
| Fix 1 (seeder slug) | 5 menit |
| Fix 2 (role owner) | 1 menit |
| Fix 3 (README) | 5 menit |
| Fix 4 (.env.example) | 3 menit |
| Fix 5 (route except show) | 5 menit |
| Fix 6 (Flutter, opsional) | 5 menit |
| Smoke test | 5 menit |
| Pack ZIP | 1 menit |
| **TOTAL** | **~30 menit** |

Setelah ini → siap share ke peserta dengan disclaimer dari file `06-disclaimer-untuk-peserta.md`.
