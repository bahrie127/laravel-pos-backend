# Disclaimer untuk Peserta — Sebelum Pakai di Usaha Real

Source code yang dishare di workshop ini siap dipakai untuk **belajar &amp; demo**, tapi **BELUM siap untuk produksi 100%**.

Kalau Anda mau pakai untuk usaha real (cafe, warung, toko), baca dokumen ini dulu. Ada beberapa hal yang **WAJIB diperbaiki dulu** supaya aman &amp; bisnis Anda tidak rugi.

---

## 🔴 KRITIKAL — Wajib Fix Sebelum Live di Cafe

### 1. Midtrans server key disimpan di Flutter app

**Yang terjadi sekarang**: Server key Midtrans Anda disimpan di SharedPreferences HP. Siapa pun yang reverse-engineer APK (orang IT iseng, kompetitor) bisa ekstrak key tersebut.

**Risiko**:
- Penyalahgunaan transaksi atas nama akun Midtrans Anda
- Charge dummy yang kena fee Midtrans ke Anda
- Refund seenaknya
- Akses ke semua histori transaksi

**Fix wajib**:
1. Pindahkan Midtrans call ke backend Laravel
2. Simpan server key di `.env` backend (tidak pernah expose ke client)
3. Backend yang call Midtrans, Flutter app cuma tampilkan QR/redirect

**Workaround sementara**: kalau belum sempat refactor, **JANGAN PAKAI live server key**. Pakai sandbox dulu sampai backend proxy ready.

### 2. Order POST tidak ada idempotency key

**Yang terjadi sekarang**: Kalau kasir checkout terus jaringan blip (200 OK dari server tapi response timeout di app), Flutter app retry kirim order. Server tidak tahu ini retry → bikin order duplikat.

**Risiko**:
- Customer di-charge 2x (kalau lewat Midtrans)
- Stok produk over-decrement
- Laporan keuangan kacau

**Fix wajib**:
1. Tambah kolom `client_uuid` UUID UNIQUE di tabel `orders` (migration baru)
2. Flutter generate `Uuid().v4()` saat buat order, simpan di local DB
3. Kirim `client_uuid` di payload `POST /api/orders`
4. Backend cek `if existing → return existing` (idempotent response)

Detail kode di `docs/qa/03-frontend-findings.md` section "Auto Sync".

---

## 🟠 PENTING — Fix Cepat Sebelum Mulai Buka Cafe

### 3. Refund tidak ada permission check di API

**Yang terjadi**: Kasir bisa refund order kasir lain via API. Hanya UI yang gate.

**Risiko**: Karyawan nakal bisa refund order kemarin → "uang masuk" kabur.

**Fix**:
- Pindahkan check ke `OrderPolicy::refund` di backend
- Tambah `$this->authorize('refund', $order)` di `RefundController::store`
- Hanya `Owner` yang boleh refund (atau buat role baru "Supervisor")

### 4. Variance shift kasir akan corrupt kalau ada refund

**Yang terjadi**: Saat refund, sistem `cash_out += refund_amount` di shift order awal. Tapi:
- Flutter app juga increment lokal
- Kalau sync, double increment
- Variance shift jadi salah

**Risiko**: Tutup shift selalu menunjukkan selisih palsu.

**Fix**: Lakukan refund hanya di salah satu (BE atau FE). Cek `is_sync` flag dulu di FE sebelum local bump.

### 5. Token API Sanctum tidak expire

**Yang terjadi**: Token login Sanctum default tanpa expiration. Kalau HP kasir hilang, attacker bisa akses sampai password kasir diganti.

**Fix di backend** (`config/sanctum.php`):
```php
'expiration' => 60 * 24 * 90, // 90 hari
```

Plus tambah scheduled job `php artisan sanctum:prune-expired` di cron.

### 6. Manage Category &amp; User tidak ada di mobile

**Yang terjadi**: Flutter app cuma read-only untuk kategori &amp; user. Tambah/edit harus via web admin.

**Implication**: Setiap kali Anda mau tambah menu baru atau karyawan baru, harus buka laptop login ke web admin. Tidak praktis untuk operasi harian cafe.

**Rekomendasi**: Tambah `manage_category_page.dart` dan `manage_user_page.dart` mirror dari `manage_product_page.dart`. Code pattern sama.

### 7. Force-close shift admin tidak ada di mobile

**Yang terjadi**: Kalau kasir lupa tutup shift dan langsung pulang, owner harus login web untuk force-close.

**Rekomendasi**: Tambah page "Kelola Shift" di mobile (owner only) yang list semua open session dengan tombol force-close.

---

## 🟡 SARAN — Hal yang Pasti Bakal Diminta User

### 8. Tax / PB1 hardcoded 0%

**Yang terjadi**: Tax calculation di Flutter `_taxPct = 0.0`. Cafe yang butuh PB1 10% tidak terpenuhi.

**Fix**: Tambah setting "Tax Percentage" di Receipt Settings. Gunakan di order calculation.

### 9. Tidak ada Customer Database

**Yang terjadi**: `customer_name` cuma string di order. Tidak ada master customer.

**Implication**: Tidak bisa lihat "siapa customer paling sering datang", tidak bisa loyalty point, tidak bisa CRM.

**Rekomendasi**: Tabel `customers` (id, name, phone, points, created_at), foreign key di orders.

### 10. Tidak ada Loyalty Point

**Yang terjadi**: Tidak ada konsep poin / member.

**Rekomendasi**: Tambah fitur earn/redeem poin. Lihat contoh prompt di `docs/slides-live-zoom-2026-06-07.html` slide "Demo: Tambah Fitur Loyalty Point".

### 11. Forgot Password tidak berfungsi

**Yang terjadi**: Backend Fortify expose endpoint `/forgot-password`, tapi UI Flutter cuma tampil snackbar "Hubungi admin".

**Risiko**: Kasir lupa password → owner harus reset manual via web admin (kalau ada).

**Fix**: Implement `ForgotPasswordPage` di Flutter, hit endpoint Fortify, kirim email reset link.

### 12. Stock minus tidak di-block

**Yang terjadi**: Kalau stok produk = 1, dua kasir order bersamaan, bisa jadi stok = -1.

**Fix backend**:
```php
DB::transaction(function () use ($request) {
    $products = Product::whereIn('id', $ids)->lockForUpdate()->get();
    foreach ($items as $it) {
        if ($products[$it['product_id']]->stock < $it['quantity']) {
            throw ValidationException::withMessages(['stock' => 'Stok tidak cukup']);
        }
    }
    // ... decrement + create order
});
```

### 13. Tidak ada Backup Database

**Yang terjadi**: Tidak ada scheduled backup harian.

**Risiko**: Server crash/corrupt → kehilangan semua data transaksi.

**Fix**: Install `spatie/laravel-backup` di backend, jadwalkan backup harian ke S3 atau Google Drive.

### 14. APP_DEBUG=true di .env.example

**Yang terjadi**: Default Anda dapat `APP_DEBUG=true`.

**Risiko di production**: error page expose stack trace + DB credentials → vulnerability.

**Fix**: Sebelum deploy ke server, **WAJIB**:
```env
APP_ENV=production
APP_DEBUG=false
```

### 15. iOS Info.plist permission strings belum verify

**Untuk Anda yang submit ke App Store**: cek `ios/Runner/Info.plist` ada:
- `NSCameraUsageDescription` (untuk scan barcode)
- `NSBluetoothPeripheralUsageDescription`
- `NSBluetoothAlwaysUsageDescription`
- `NSPhotoLibraryUsageDescription` (untuk upload foto produk)

Kalau tidak ada → app crash di runtime saat minta permission.

---

## ✅ Yang Sudah Aman &amp; Bisa Langsung Dipakai

Berikut fitur yang **sudah solid** dan bisa Anda pakai tanpa khawatir:

- ✅ Login/logout web &amp; API (Fortify + Sanctum)
- ✅ Hapus akun (Play Store compliance)
- ✅ Privacy policy public URL
- ✅ Cetak struk thermal Bluetooth (58mm &amp; 80mm)
- ✅ Branding struk customizable (store name, alamat, footer, logo)
- ✅ Offline-first order create (SQLite outbox)
- ✅ Auto sync setelah online
- ✅ Buka shift dengan opening_float
- ✅ Tutup shift dengan variance calculation
- ✅ Apply promo voucher (cek server)
- ✅ Order detail + invoice PDF + export Excel
- ✅ Dashboard real-time
- ✅ 7 jenis laporan (summary, product sales, close cashier, promo usage, sales analytics, inventory)

---

## Roadmap untuk Production (Saran Urutan)

**Week 1** — Fix critical:
1. Pindah Midtrans ke backend proxy
2. Tambah idempotency key di order push
3. Refactor refund permission &amp; double-debit issue

**Week 2** — Fix high priority:
4. Tambah manage category &amp; user di Flutter
5. Force-close shift admin di mobile
6. Token Sanctum expiration
7. Stock race condition

**Week 3** — Nice to have:
8. Tax configuration
9. Forgot password flow
10. Customer database + Loyalty point
11. iOS Info.plist audit
12. Backup scheduler

**Week 4** — Hardening:
13. Unit testing (target 70% coverage)
14. CI/CD (GitHub Actions)
15. Monitoring (Sentry/Telescope)
16. Load testing (artillery atau k6)

---

## Saran Komunikasi ke Customer Cafe

Kalau Anda jual jasa setup POS ke cafe customer:

> "POS ini punya semua fitur dasar yang Anda butuh: kasir, struk thermal, laporan, promo. **Beberapa fitur lanjut** seperti integrasi Midtrans untuk QRIS auto-settle, customer loyalty, dan multi-cabang **dikenakan biaya tambahan** karena perlu development khusus."

Jangan janjikan fitur yang belum ada. **Set ekspektasi sejak awal**.

---

## Pertanyaan / Bantuan

- Diskusi di Grup Telegram FIC11
- Email: saiful.bahri.tl@gmail.com
- YouTube Code with Bahri — tutorial lanjutan

**Good luck dengan usaha Anda!**
