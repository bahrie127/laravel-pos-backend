# Step 06 — Helper Format + i18n Indonesia

## Tujuan

Mempunyai **helper global** untuk format Rupiah, tanggal, inisial nama, dan **lokalisasi Bahasa Indonesia** penuh (auth, validation, custom messages).

## Prasyarat

- Step 05 selesai

## Konteks

Format duit "Rp 1.250.000" dan tanggal "24 Mei 2026 14:30" tersebar di puluhan view. Tanpa helper, copy-paste `number_format()` di mana-mana. Dengan helper, panggil `rupiah(1250000)` selesai. Bonus: kalau format Rupiah berubah (misal pakai koma), tinggal edit 1 file.

## Prompt untuk AI

````
Project Laravel POS sudah punya layout + komponen. Sekarang setup helper format & i18n Bahasa Indonesia.

A. HELPER FUNCTIONS
1. Edit `app/Helpers/format.php` (sudah di-autoload dari Step 01):

```php
<?php

if (! function_exists('rupiah')) {
    /**
     * Format angka jadi string Rupiah Indonesia.
     * rupiah(1250000) → "Rp 1.250.000"
     * rupiah(1250000, false) → "1.250.000"
     */
    function rupiah(int|float|null $amount, bool $withPrefix = true): string
    {
        $amount = $amount ?? 0;
        $formatted = number_format((float) $amount, 0, ',', '.');
        return $withPrefix ? 'Rp ' . $formatted : $formatted;
    }
}

if (! function_exists('formatDate')) {
    /**
     * Format datetime fleksibel pakai Carbon dengan locale aktif.
     * formatDate('2026-05-24 14:30') → "24 Mei 2026 14:30"
     * formatDate($order->transaction_time, 'd M Y') → "24 Mei 2026"
     */
    function formatDate(mixed $value, string $format = 'd M Y H:i', string $fallback = '—'): string
    {
        if (empty($value)) return $fallback;
        try {
            return \Carbon\Carbon::parse($value)->translatedFormat($format);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}

if (! function_exists('initials')) {
    /**
     * Ambil 2 huruf inisial dari nama untuk avatar fallback.
     * initials("Code with Bahri") → "CB"
     * initials("Bahri") → "BA"
     */
    function initials(?string $name): string
    {
        if (empty($name)) return '?';
        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
        }
        return strtoupper(mb_substr($parts[0], 0, 2));
    }
}
```

B. CARBON LOCALE
2. Edit `app/Providers/AppServiceProvider.php`:

```php
public function boot(): void
{
    Paginator::useBootstrapFour();
    Carbon::setLocale(config('app.locale', 'id'));
    setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'id');
}
```

3. Pastikan `config/app.php`:
   - `'locale' => 'id'`
   - `'faker_locale' => 'id_ID'`

C. FILE BAHASA INDONESIA
4. Buat `lang/id/auth.php`:
```php
return [
    'failed' => 'Email atau password salah.',
    'password' => 'Password yang dimasukkan salah.',
    'throttle' => 'Terlalu banyak percobaan login. Coba lagi dalam :seconds detik.',
];
```

5. Buat `lang/id/passwords.php`:
```php
return [
    'reset' => 'Password Anda telah direset.',
    'sent' => 'Link reset password telah dikirim ke email Anda.',
    'throttled' => 'Tunggu sebelum mencoba lagi.',
    'token' => 'Token reset password tidak valid.',
    'user' => 'Email tidak terdaftar.',
];
```

6. Buat `lang/id/pagination.php`:
```php
return [
    'previous' => '« Sebelumnya',
    'next' => 'Berikutnya »',
];
```

7. Buat `lang/id/validation.php` — lengkap (copy template dari Laravel-lang/lang Github repo, atau buat manual untuk rule yang dipakai: required, email, min, max, unique, exists, confirmed, image, integer, numeric, in, between, date, after, before).

8. Buat `lang/id/messages.php` — kustom string UI:
```php
return [
    // Aksi umum
    'save' => 'Simpan',
    'save_and_new' => 'Simpan & Tambah Lagi',
    'cancel' => 'Batal',
    'delete' => 'Hapus',
    'edit' => 'Edit',
    'create' => 'Tambah',
    'back' => 'Kembali',
    'filter' => 'Filter',
    'reset' => 'Reset',
    'search' => 'Cari',
    'export' => 'Export',
    'print' => 'Cetak',
    'detail' => 'Lihat',

    // Feedback CRUD
    'created' => ':resource berhasil ditambahkan.',
    'updated' => ':resource berhasil diperbarui.',
    'deleted' => ':resource berhasil dihapus.',
    'not_found' => ':resource tidak ditemukan.',
    'unauthorized' => 'Anda tidak memiliki akses.',

    // Konfirmasi
    'confirm_delete' => 'Yakin hapus?',
    'confirm_delete_text' => 'Aksi tidak bisa dibatalkan.',

    // Empty state
    'no_data' => 'Belum ada data.',
    'no_orders' => 'Belum ada pesanan.',
    'no_products' => 'Belum ada produk.',

    // Status / generic
    'status' => 'Status',
    'active' => 'Aktif',
    'inactive' => 'Nonaktif',
    'all' => 'Semua',
    'showing' => 'Menampilkan :from-:to dari :total',
];
```

D. KONFIGURASI
9. Pastikan `config/app.php` set fallback ke `id`:
   ```php
   'locale' => env('APP_LOCALE', 'id'),
   'fallback_locale' => env('APP_FALLBACK_LOCALE', 'id'),
   ```

10. Tambah `setLocale(app()->getLocale())` di awal `app()->setLocale('id')` di `AppServiceProvider::boot()` (sebenarnya sudah handle via config).

E. TEST
11. Buat route test `/_helpers-test` (di routes/web.php) untuk validasi:
    ```php
    Route::get('/_helpers-test', function () {
        return [
            'rupiah' => rupiah(1250000),
            'rupiah_no_prefix' => rupiah(1250000, false),
            'formatDate_default' => formatDate(now()),
            'formatDate_custom' => formatDate(now(), 'l, d F Y'),
            'initials' => initials('Code with Bahri'),
            'auth_failed' => __('auth.failed'),
            'msg_save' => __('messages.save'),
        ];
    });
    ```
    Hapus route ini setelah verifikasi.

Tampilkan output dari `/_helpers-test` setelah selesai.
````

## Hasil yang Diharapkan

```
app/
├── Helpers/format.php           ← rupiah(), formatDate(), initials()
└── Providers/AppServiceProvider.php  ← Carbon::setLocale('id')

lang/id/
├── auth.php
├── messages.php
├── pagination.php
├── passwords.php
└── validation.php
```

Output `/_helpers-test`:
```json
{
  "rupiah": "Rp 1.250.000",
  "rupiah_no_prefix": "1.250.000",
  "formatDate_default": "07 Juni 2026 ...",
  "formatDate_custom": "Minggu, 07 Juni 2026",
  "initials": "CB",
  "auth_failed": "Email atau password salah.",
  "msg_save": "Simpan"
}
```

## Cara Test

```bash
# 1. Test via tinker
php artisan tinker
>>> rupiah(1250000)
=> "Rp 1.250.000"
>>> formatDate(now())
=> "07 Juni 2026 14:30"
>>> initials('Code with Bahri')
=> "CB"
>>> __('messages.save')
=> "Simpan"

# 2. Test via route helpers
curl http://localhost:8000/_helpers-test

# 3. Sengaja salahkan login (Step 03) — error message harus Bahasa Indonesia ("Email atau password salah.")

# 4. Submit form kosong di /forgot-password → error required "wajib diisi" (Bahasa Indonesia)
```

## Penjelasan untuk Murid

Talking points:

1. **"Kenapa helper global lebih baik daripada Trait?"**
   - Helper: 1 baris call. `rupiah($x)`.
   - Trait: butuh `use SomeTrait` di class. Lebih boilerplate.
   - View di-pass dari controller? Helper-nya bisa langsung dipanggil di Blade.

2. **"`translatedFormat()` vs `format()`?"**
   - `format('d M Y')` → "07 Jun 2026" (English).
   - `translatedFormat('d M Y')` → "07 Jun 2026" tapi dengan locale = "07 Jun 2026" (Indonesian)
   - Kalau set `setLocale('id')`, "Jun" jadi "Jun" tetap (singkatan sama), tapi `formatDate(now(), 'F')` jadi "Juni" instead of "June".

3. **"Apa beda `__()` vs `trans()` vs `@lang()`?"** — Semua sama. Sintaks beda saja. `__()` paling umum di blade.

4. **"Locale `id_ID` di `setlocale()` butuh ada di server?"** — Yup, butuh locale OS terinstall. Di Mac biasanya udah. Di Linux: `sudo locale-gen id_ID.UTF-8`.

5. **"Fallback locale apa fungsinya?"** — Kalau `lang/id/messages.php` tidak punya key, Laravel cari di fallback. Kalau fallback juga `id`, ya tampil key-nya. Idealnya fallback `en` supaya jelas ada yang missing.

6. **"Bisakah dynamic locale per user?"** — Bisa. Simpan preference di kolom `users.locale`, lalu di middleware: `app()->setLocale($user->locale ?? 'id')`. POS pakai single locale untuk simplicity.

Pertanyaan reflektif:
- "Kalau client minta tambah Bahasa Inggris untuk versi ekspor laporan, langkah apa?" (→ buat `lang/en/`, switch via query string atau setting)
- "Format mata uang USD: \$1,250,000. Helper-nya gimana?" (→ `number_format($x, 2, '.', ',')` + prefix `$`)

---

**Next: [07-models-migrations-pos.md](07-models-migrations-pos.md)**
