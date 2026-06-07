# Step 01 — Setup Project Laravel

## Tujuan

Memiliki **project Laravel 13 fresh** + semua composer package terinstall + `.env` dasar siap.

## Prasyarat

- PHP 8.3+ (`php -v`)
- Composer 2.x (`composer -V`)
- MySQL berjalan
- Folder kerja: `/Users/bahri/development/FIC11Jilid2/`

## Konteks

Kita mulai dari fresh Laravel install. Semua dependency POS (Fortify, Sanctum, DomPDF, Excel, Heroicons, Resend) diinstall sekaligus di step ini supaya step berikutnya bisa fokus pada koding fitur.

## Prompt untuk AI

````
Saya mau membuat aplikasi POS backend dengan Laravel 13. Tolong:

1. Buat project Laravel baru dengan composer:
   composer create-project laravel/laravel:^13.0 laravel-pos-backend-prejilid2
   cd laravel-pos-backend-prejilid2

2. Install semua dependency berikut sekaligus:
   composer require \
     barryvdh/laravel-dompdf:^3.1 \
     blade-ui-kit/blade-heroicons:^2.7 \
     guzzlehttp/guzzle:^7.8 \
     laravel/fortify:^1.37 \
     laravel/sanctum:^4.3 \
     laravel/tinker \
     maatwebsite/excel:^3.1 \
     resend/resend-laravel:^1.0

3. Install dev dependencies:
   composer require --dev laravel/pint laravel/sail mockery/mockery nunomaduro/collision phpunit/phpunit spatie/laravel-ignition

4. Update `composer.json` autoload untuk include helper file:
   ```json
   "autoload": {
       "psr-4": {
           "App\\": "app/",
           "Database\\Factories\\": "database/factories/",
           "Database\\Seeders\\": "database/seeders/"
       },
       "files": [
           "app/Helpers/format.php"
       ]
   }
   ```
   Lalu jalankan `composer dump-autoload`.

5. Update `.env` dengan nilai berikut:
   ```
   APP_NAME="POS FIC11"
   APP_ENV=local
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
   
   MAIL_MAILER=log
   ```

6. Generate APP_KEY: `php artisan key:generate`

7. Buat file kosong `app/Helpers/format.php` dengan komentar:
   ```php
   <?php
   // Format helpers (rupiah, formatDate, initials) — diisi di Step 06.
   ```

8. Buat folder `docs/` dan `lang/id/` (kalau belum ada) untuk persiapan step berikutnya.

Setelah selesai, tampilkan output `php artisan --version` dan `composer show | head -30` untuk verifikasi.
````

## Hasil yang Diharapkan

```
laravel-pos-backend-prejilid2/
├── app/
│   └── Helpers/format.php           ← baru, masih komentar
├── docs/                             ← baru
├── lang/id/                          ← baru
├── .env                              ← APP_NAME="POS FIC11", DB config, locale id
├── composer.json                     ← autoload.files: app/Helpers/format.php
└── (rest of Laravel skeleton)
```

Output `composer show` harus memuat (versi minimum):
- `barryvdh/laravel-dompdf` >=3.1
- `blade-ui-kit/blade-heroicons` >=2.7
- `laravel/fortify` >=1.37
- `laravel/framework` >=13.0
- `laravel/sanctum` >=4.3
- `maatwebsite/excel` >=3.1
- `resend/resend-laravel` >=1.0

## Cara Test

```bash
# 1. Versi Laravel
php artisan --version
# expect: Laravel Framework 13.x.x

# 2. Routing default jalan
php artisan serve
# Buka http://localhost:8000 → harus tampil halaman welcome Laravel

# 3. Verifikasi autoload helper
composer dump-autoload
# Output mention: "Generated optimized autoload files"

# 4. Cek .env
grep -E "APP_NAME|APP_LOCALE|DB_DATABASE" .env
# expect: APP_NAME="POS FIC11", APP_LOCALE=id, DB_DATABASE=fic11jilid2-db

# 5. Database connection (akan error 'tabel tidak ada' — itu OK, yang penting koneksi sukses)
php artisan db:show
# expect: tabel kosong tapi info DB tampil
```

## Penjelasan untuk Murid

Talking points:

1. **"Kenapa install semua dependency sekaligus?"** — Karena POS adalah aplikasi feature-rich. Daripada install bertahap (`composer require X` 7x) dan kena rate limit Packagist, kita gabung jadi satu transaksi. Composer akan resolve semua versi sekaligus.

2. **"Apa itu DomPDF, Excel, Resend?"**
   - DomPDF: render Blade view ke PDF (untuk invoice & report PDF).
   - Maatwebsite Excel: export data ke XLSX (untuk orders & reports).
   - Resend: kirim email via Resend API (untuk email report scheduled).

3. **"Kenapa locale `id`?"** — Carbon akan auto-format tanggal dengan bulan Indonesia (`Mei`, `Juni`). Validator error message juga Bahasa Indonesia.

4. **"Kenapa autoload.files?"** — Helper function global (`rupiah()`, `formatDate()`) di-load otomatis tiap request tanpa perlu `use App\Helpers\...`. Pattern ini standar untuk view helper.

5. **"Kenapa MAIL_MAILER=log?"** — Saat development, email di-log ke `storage/logs/laravel.log` instead of beneran kirim. Lebih aman + cepat.

Pertanyaan reflektif:
- "Kalau Composer Anda lambat, apa solusinya?" (→ pakai mirror Packagist Indonesia, atau enable `~/.composer/config.json` repositories `composer-mirror`)
- "Kenapa `APP_DEBUG=true` cuma untuk development?" (→ di production akan expose stack trace = security risk)

---

**Next: [02-database-baseline.md](02-database-baseline.md)**
