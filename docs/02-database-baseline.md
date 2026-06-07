# Step 02 — Database Baseline

## Tujuan

Mempunyai **database `fic11jilid2-db`** berisi tabel baseline Laravel: `users`, `password_reset_tokens`, `failed_jobs`, `personal_access_tokens`, dan kolom 2FA siap untuk Fortify.

## Prasyarat

- Step 01 selesai (project Laravel + composer deps siap)
- MySQL service berjalan

## Konteks

Sebelum bikin tabel POS (products, orders, dll), kita pasang dulu fondasi yang dibutuhkan Fortify (2FA) dan Sanctum (personal_access_tokens). Migration ini di-`fresh` sekali, lalu tidak akan diutak-atik lagi.

## Prompt untuk AI

````
Lanjutkan project Laravel POS di `/Users/bahri/development/FIC11Jilid2/laravel-pos-backend-prejilid2`. Database belum disiapkan. Tolong:

1. Buat database MySQL `fic11jilid2-db` (atau anggap sudah ada).

2. Pastikan migration baseline berikut sudah ada di `database/migrations/`:
   - `2014_10_12_000000_create_users_table.php`
   - `2014_10_12_100000_create_password_reset_tokens_table.php`
   - `2019_08_19_000000_create_failed_jobs_table.php`
   - `2019_12_14_000001_create_personal_access_tokens_table.php`

   (Empat file ini default Laravel; pastikan tetap ada.)

3. Buat migration baru `2014_10_12_200000_add_two_factor_columns_to_users_table.php`:
   ```php
   Schema::table('users', function (Blueprint $table) {
       $table->text('two_factor_secret')->nullable()->after('password');
       $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
       $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
   });
   ```
   Reverse di `down()`: drop ketiga kolom tersebut.

4. Jalankan `php artisan migrate:fresh` dan tampilkan output.

5. Tampilkan `php artisan db:show --counts` untuk verifikasi semua tabel terbuat dengan 0 row.

Jangan dulu bikin migration POS (products, categories, orders) — itu di Step 07.
````

## Hasil yang Diharapkan

```
database/migrations/
├── 2014_10_12_000000_create_users_table.php           ← default
├── 2014_10_12_100000_create_password_reset_tokens_table.php  ← default
├── 2014_10_12_200000_add_two_factor_columns_to_users_table.php  ← BARU
├── 2019_08_19_000000_create_failed_jobs_table.php     ← default
└── 2019_12_14_000001_create_personal_access_tokens_table.php  ← default
```

Setelah `migrate:fresh`, tabel yang ada:
- `users` (id, name, email, email_verified_at, password, two_factor_*, remember_token, timestamps)
- `password_reset_tokens` (email PK, token, created_at)
- `failed_jobs`
- `personal_access_tokens` (untuk Sanctum)
- `migrations` (history tracker)

## Cara Test

```bash
# 1. Migrate fresh (drop semua tabel + rerun semua migration)
php artisan migrate:fresh

# Expected output:
# Dropped all tables successfully.
# INFO  Preparing database.
# Creating migration table ... DONE
# INFO  Running migrations.
# 2014_10_12_000000_create_users_table ............... DONE
# 2014_10_12_100000_create_password_reset_tokens_table . DONE
# 2014_10_12_200000_add_two_factor_columns_to_users_table . DONE
# 2019_08_19_000000_create_failed_jobs_table ......... DONE
# 2019_12_14_000001_create_personal_access_tokens_table . DONE

# 2. Cek struktur tabel users
php artisan db:table users
# Verifikasi ada kolom: two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at

# 3. Hitung tabel
php artisan db:show --counts
# Expected: 5 tabel, semua 0 row
```

## Penjelasan untuk Murid

Talking points:

1. **"Kenapa migration urut by timestamp di nama file?"** — Laravel jalankan migration sesuai urutan alfabetik nama file. Timestamp jadi natural ordering. Sangat penting untuk dependency: e.g., `add_roles_phone_at_users` butuh `users` table sudah dibuat.

2. **"Kenapa kolom 2FA dipisah sendiri migration-nya?"** — Best practice: jangan ubah migration lama (`create_users_table`) kalau sudah deployed. Buat migration baru `add_X_to_users` supaya history clean dan rollback bisa per-step.

3. **"Apa itu `personal_access_tokens`?"** — Tabel Sanctum untuk simpan API token. Kolom `tokenable_type` + `tokenable_id` polymorphic — bisa attach token ke model apa saja, bukan cuma User.

4. **"Kenapa `php artisan migrate:fresh` bukan `migrate`?"**
   - `migrate`: jalankan migration yang belum jalan.
   - `migrate:fresh`: drop SEMUA tabel, lalu rerun semua migration dari awal. Dipakai saat development untuk reset clean.
   - `migrate:refresh`: rollback semua → migrate ulang (lebih lambat, sama hasilnya).
   - **Production**: WAJIB pakai `migrate` (jangan `fresh`!).

5. **"Apa itu `remember_token`?"** — Token untuk fitur "Ingat saya" di form login. Disimpan di cookie persistent.

Pertanyaan reflektif:
- "Kalau Anda ubah nama kolom `name` jadi `full_name`, langkahnya apa?" (→ buat migration baru pakai `renameColumn`, butuh package `doctrine/dbal`)
- "Kalau migration error di tengah, gimana state DB?" (→ DBMS belum commit transaction = rollback otomatis kalau pakai DB engine InnoDB)

---

**Next: [03-auth-fortify-sanctum.md](03-auth-fortify-sanctum.md)**
