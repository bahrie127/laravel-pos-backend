# Laravel POS Backend — FIC11 Jilid 2

Backend Laravel untuk aplikasi POS (Point of Sale) yang dipakai oleh Flutter mobile app. Project ini berisi REST API (Sanctum-protected) untuk app kasir, plus admin web panel (Fortify auth) untuk manajemen master data dan reporting.

## Stack

| Komponen | Versi |
|---|---|
| Laravel Framework | **13.11.2** |
| PHP | **^8.3** (diuji pada 8.3.22) |
| laravel/sanctum | ^4.3 (API token auth) |
| laravel/fortify | ^1.37 (web auth scaffolding) |
| laravel/tinker | ^3.0 |
| resend/resend-laravel | ^1.0 (mail driver) |
| PHPUnit | ^12 |
| Database | MySQL |

## Struktur Utama

```
app/
├── Http/
│   ├── Controllers/        # Web controllers (User, Product, Order, Category)
│   ├── Controllers/Api/    # API controllers (Auth, Product, Order, Category, Report)
│   ├── Middleware/         # Laravel 10-style middleware (Kernel.php)
│   └── Kernel.php
├── Models/                 # User, Product, Category, Order, OrderItem
├── Actions/Fortify/        # Fortify user actions
└── Providers/              # AppServiceProvider, RouteServiceProvider, FortifyServiceProvider, dll
database/migrations/        # 12 migrations (users, products, orders, order_items, categories, dll)
resources/views/pages/      # auth, categories, dashboard, orders, products, users
routes/
├── api.php                 # REST endpoints (Sanctum)
└── web.php                 # Admin panel
```

Catatan: Project ini masih memakai struktur Laravel 10 lama (`bootstrap/app.php` klasik, `app/Http/Kernel.php`, `app/Console/Kernel.php`, `app/Exceptions/Handler.php`, `RouteServiceProvider`). Struktur ini tetap kompatibel di Laravel 13 (backwards compat) — tidak dimigrasikan ke `Application::configure()` style baru.

## API Endpoints

Semua endpoint di bawah `/api` dan (kecuali `login`) dilindungi `auth:sanctum`.

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/login` | Login, kembalikan plain text token |
| POST | `/api/logout` | Hapus current token |
| GET | `/api/user` | Info user yang login |
| GET/POST/PUT/DELETE | `/api/products` | Resource Product |
| GET/POST/PUT/DELETE | `/api/orders` | Resource Order |
| GET | `/api/orders/kasir/{kasir_id}` | Order milik kasir tertentu |
| GET | `/api/list-categories` | List kategori |
| GET | `/api/reports/summary` | Ringkasan transaksi |
| GET | `/api/reports/product-sales` | Penjualan per produk |
| GET | `/api/reports/close-cashier` | Tutup kasir |

## Admin Web Panel

Setelah login via Fortify (`/login`), tersedia route resource untuk: `user`, `product`, `order`, `categories`, plus dashboard di `/home`. UI dibangun di atas Bootstrap (lihat `public/library/` untuk CSS/JS vendor assets — chart.js, datatables, dropzone, fullcalendar, dll).

## Setup Lokal

```bash
# 1. Clone & install dependencies
composer install

# 2. Copy env dan generate APP_KEY
cp .env.example .env
php artisan key:generate

# 3. Atur DB credential di .env (default: mysql, db name `fic11jilid2-db`)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=fic11jilid2-db
# DB_USERNAME=root
# DB_PASSWORD=

# 4. Buat database
mysql -u root -e "CREATE DATABASE \`fic11jilid2-db\`;"

# 5. Migrate + seed (WAJIB --seed supaya ada akun owner siap login)
php artisan migrate --seed

# 6. Symlink storage (WAJIB supaya image upload bisa diakses via URL)
php artisan storage:link

# 7. Jalankan dev server
php artisan serve
# → http://localhost:8000
```

### Akun Default (dari seeder)

| Email | Password | Role |
|---|---|---|
| `bahri@fic11.com` | `12345678` | Owner (akses penuh) |
| `admin@fic11.com` | `12345678` | Admin |
| (8 akun kasir random) | `password` | Kasir |

### Troubleshooting

| Error | Fix |
|---|---|
| `SQLSTATE[HY000] [1049] Unknown database 'fic11jilid2-db'` | Buat DB dulu: `mysql -u root -e "CREATE DATABASE \`fic11jilid2-db\`;"` |
| Image upload tidak muncul / 404 | `php artisan storage:link` |
| `Class "Resend" not found` saat akses endpoint report API | Set `RESEND_API_KEY` di `.env` atau jangan panggil endpoint tersebut |
| Login web → "Email atau password salah" | Pastikan sudah jalan `php artisan migrate --seed` |
| Route ke `/product/{id}` tanpa `/edit` → BadMethodCallException | Sudah di-fix di `routes/web.php` dengan `->except(['show'])` |

## Testing

```bash
php artisan test
```

## Frontend (Flutter)

Project FE mobile app yang mengkonsumsi API ini berada di:

```
/Users/bahri/development/fic11/flutter_pos_app
```

## Branch

- `master` — main branch
- `fulljilid2` — branch aktif (Laravel 13 upgrade)

Remote: `git@github.com:bahrie127/laravel-pos-backend.git`

## License

MIT.
