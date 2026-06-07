# Tutorial Step-by-Step: Membangun Laravel POS Backend (FIC11 Jilid 2)

Materi mengajar lengkap untuk membangun sistem POS berbasis Laravel 13 + Sanctum + Fortify + Bootstrap (Stisla). Setiap file di folder ini adalah **satu step yang self-contained**: berisi konteks, prompt siap-lempar ke AI, hasil yang diharapkan, cara test, dan talking point untuk presenter.

## Cara Pakai Dokumen Ini

1. Buka file step sesuai urutan (00 → 19).
2. Di tiap step, copy bagian **"Prompt untuk AI"** ke chat dengan AI coding assistant.
3. AI akan generate kode. Verifikasi dengan bagian **"Hasil yang Diharapkan"**.
4. Jalankan bagian **"Cara Test"** untuk pastikan jalan.
5. Saat presenting ke kelas, baca bagian **"Penjelasan untuk Murid"** untuk talking points.

## Daftar Step

| # | File | Topik | Output Utama |
|---|---|---|---|
| 00 | [00-overview.md](00-overview.md) | Pengantar, stack, roadmap | Pemahaman arsitektur |
| 01 | [01-setup-project.md](01-setup-project.md) | Setup project Laravel 13 + composer deps | Project fresh + `.env` siap |
| 02 | [02-database-baseline.md](02-database-baseline.md) | Konfigurasi DB + migration baseline (users, tokens, sessions) | DB connected + migrate `:fresh` jalan |
| 03 | [03-auth-fortify-sanctum.md](03-auth-fortify-sanctum.md) | Install Fortify (web) + Sanctum (API) | Login web + token mobile |
| 04 | [04-layout-sidebar.md](04-layout-sidebar.md) | Layout `app.blade.php` + `auth.blade.php` + sidebar + header (Stisla theme) | Skeleton UI siap |
| 05 | [05-blade-components.md](05-blade-components.md) | 22 komponen Blade reusable (`<x-form-input>`, `<x-card>`, dll) | Library komponen |
| 06 | [06-helpers-i18n.md](06-helpers-i18n.md) | Helper `rupiah()`, `formatDate()`, locale `id` | Format konsisten |
| 07 | [07-models-migrations-pos.md](07-models-migrations-pos.md) | Model & migration POS: Product, Category, Order, OrderItem (+ Phase 2 enhance) | Schema POS lengkap |
| 08 | [08-seeders.md](08-seeders.md) | DatabaseSeeder + ProductSeeder + factories | Data dummy untuk dev |
| 09 | [09-role-policy.md](09-role-policy.md) | `UserRole` enum + 6 Policy + middleware `role:` | Role-based access control |
| 10 | [10-dashboard.md](10-dashboard.md) | `DashboardController` + view real (4 stat card + top produk + recent orders + low stock + chart pembayaran) | Dashboard live |
| 11 | [11-categories-crud.md](11-categories-crud.md) | CRUD Categories (grid+list, icon/color picker, slug auto) | Master data kategori |
| 12 | [12-products-crud.md](12-products-crud.md) | CRUD Products (filter, sort, bulk delete, upload, currency mask, best-seller) | Master data produk |
| 13 | [13-users-crud.md](13-users-crud.md) | CRUD Users (avatar upload, role badge, status, last login) | Manage user |
| 14 | [14-profile.md](14-profile.md) | Profile tabs (Info, Password, Hapus Akun) | Self-service profile |
| 15 | [15-orders.md](15-orders.md) | Orders index + detail + struk thermal + invoice PDF + export Excel | Transaksi lengkap |
| 16 | [16-cash-sessions.md](16-cash-sessions.md) | Cash Session (buka/tutup shift, variance, force-close) | Manajemen shift kasir |
| 17 | [17-promos.md](17-promos.md) | Promo (persen/rupiah/B1G1) + apply ke order | Voucher & diskon |
| 18 | [18-reports.md](18-reports.md) | 7 jenis report + filter date range + export xlsx/csv/pdf/print | Laporan lengkap |
| 19 | [19-api-mobile.md](19-api-mobile.md) | API Sanctum untuk Flutter (auth, product, order, refund, promo, cash session) | Backend mobile siap |

## Prasyarat Umum (Siapkan Sekali di Awal)

- PHP 8.3+
- Composer 2.x
- MySQL 8 / MariaDB 10.6+
- Node.js 18+ (untuk Vite, opsional Phase 1)
- Editor (VS Code / PhpStorm)
- Browser modern + Postman/Insomnia (untuk test API)

## Konvensi di Tiap Step

Setiap file step menggunakan struktur yang sama:

```
# Step N — Judul

## Tujuan
Apa yang dicapai di akhir step ini.

## Prasyarat
Step sebelumnya yang wajib selesai.

## Konteks
Kenapa kita melakukan ini, problem apa yang dipecahkan.

## Prompt untuk AI
(Copy-paste ke AI assistant. Self-contained, tidak butuh konteks tambahan.)

## Hasil yang Diharapkan
File yang dibuat/diubah + isi singkat tiap file.

## Cara Test
Langkah verifikasi manual + perintah artisan.

## Penjelasan untuk Murid
Talking points + pertanyaan reflektif untuk diskusi kelas.
```

## Catatan Strategis

- **Bahasa default UI**: Bahasa Indonesia (string disimpan di `lang/id/`)
- **Style guide**: Bootstrap 5 + Stisla theme (jangan migrasi ke Tailwind untuk Phase 1-2)
- **Database**: MySQL, default name `fic11jilid2-db`
- **Penamaan brand**: `POS FIC11` (set di `.env` `APP_NAME`)
- **Frontend mobile reference**: `/Users/bahri/development/fic11/flutter_pos_app`

Selamat mengajar! 🚀
