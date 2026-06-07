# Frontend (Flutter) Audit Findings

Hasil eksplorasi Flutter POS app di `/Users/bahri/development/fic11/flutter_pos_app`.

**Score per area** (0-10):

| Area | Score | Catatan |
|---|---|---|
| Architecture &amp; State | 8 | Bloc clean, Freezed sealed, codegen rapi |
| Offline-first (core flow) | 8 | SQLite outbox solid |
| Auto Sync | 6 | **Missing idempotency** |
| Open/Close Kasir | 8 | Lengkap dengan guard |
| CRUD Master Data | 4 | **Category &amp; User tidak ada** |
| Order Flow | 7 | Transfer placeholder, tax hardcoded |
| Printer Bluetooth | 8 | 58/80mm, branding custom |
| Midtrans | 4 | **Server key di client!** |
| Refund | 6 | Hanya full refund, no role guard |
| Auth | 6 | No 401 handler, no forgot password |
| Build Android | 8 | Signing, permission rapi |
| Build iOS | 4 | Info.plist permission strings belum verify |
| Missing Cafe Features | 4 | No tax, no customer DB, no KOT terpisah |

**Average: 6.4 / 10**

---

## 1. Architecture &amp; Stack

✅ **State management**: `flutter_bloc ^8.1.3` (18 BlocProvider di `lib/main.dart`)
✅ **HTTP**: `http ^1.1.2` (manual, no Dio interceptor)
✅ **Local DB**: `sqflite ^2.3.0` schema v7 di `lib/data/datasources/product_local_datasource.dart`
✅ **Secure storage**: `flutter_secure_storage ^9.2.4` untuk token (Android Keystore + iOS Keychain)
✅ **Printer**: `print_bluetooth_thermal ^1.1.6` + `esc_pos_utils_plus ^2.0.3`

Folder structure:
```
lib/
├── core/{constants, components, theme, services, bloc}
├── data/{datasources, models/{request,response}, dataoutputs(printer)}
└── presentation/
    ├── auth/, cash_session/, draft_order/, history/, home/
    ├── order/, promo/, refund/, setting/
```

Hybrid feature/layer — bukan strict Clean Architecture tapi rapi. Tidak ada `domain/` atau `repositories/`. Datasource langsung dipakai bloc.

---

## 2. Offline-First Implementation

✅ **Pattern**: cache-aside + outbox.
- Reads (produk, kategori, promo) selalu dari local DB.
- Writes (order, refund) save ke local dulu dengan `is_sync = 0`.
- Stock decrement lokal dalam SQLite transaction.

✅ **Fitur jalan offline**:
- Create order tunai/QRIS (kalau ada cache produk)
- Open Bill / draft order
- Refund (local marking, sync nanti)
- Browse history (local fallback)
- Cetak struk

❌ **Tidak jalan offline**:
- Login (no offline cache untuk credential)
- Buka/tutup shift (BE source of truth)
- Apply voucher code (`/promos/apply` server-validated)

**Verdict**: ✅ Acceptable. Offline mode cover skenario utama POS (transaksi).

---

## 3. Auto Sync ⚠️

**File kunci**: `lib/presentation/setting/bloc/sync/sync_bloc.dart`

✅ **Trigger sync**:
1. Connectivity restored (`BlocListener` di `main.dart:102-108`)
2. Bootstrap setelah login (`SplashPage`)
3. Manual dari Settings → "Sinkronisasi Data"

✅ **Order benar**: push orders dulu (supaya stock BE ter-decrement), baru pull products (hindari double-decrement).

🔴 **MASALAH 1 — Idempotency missing**:

```dart
// OrderRequestModel tidak punya client_uuid
// Kalau retry karena network blip (200 OK tapi response timeout),
// order bisa DUPLIKAT di server.
```

**Risk**: kasir buat order Rp 100.000 → network blip → FE retry → BE process 2x → 2 order @ Rp 100.000 → laporan kacau, stok over-decrement.

**Fix wajib sebelum production**:
```dart
// lib/data/models/request/order_request_model.dart
class OrderRequestModel {
  final String clientUuid;  // generate dengan Uuid().v4()
  ...
  Map<String, dynamic> toMap() => {
    'client_uuid': clientUuid,
    ...
  };
}
```

Di BE:
```php
// orders migration
$table->uuid('client_uuid')->nullable()->unique();

// OrderController::store
$existing = Order::where('client_uuid', $request->client_uuid)->first();
if ($existing) {
    return ApiResponse::success(new OrderResource($existing), 'Already exists', 200);
}
```

🟠 **MASALAH 2 — No retry-with-backoff**:
1 attempt per trigger. Kalau fail, tunggu connectivity event atau manual trigger. Bisa miss.

🟠 **MASALAH 3 — No periodic sync**:
Tidak ada timer 5-menit polling. Kalau koneksi instable, banyak pending order menumpuk.

🟢 **Conflict resolution**:
Pull = replace-all (`removeAllProduct` → `insertAllProduct`). Local edit yang belum push akan hilang. **OK untuk produk read-only di mobile**, tapi kalau kasir tambah produk di mobile, edit lokal hilang.

---

## 4. Open/Close Kasir ✅

**Files**:
- `lib/presentation/cash_session/pages/buka_kasir_page.dart`
- `lib/presentation/cash_session/pages/tutup_kasir_page.dart`

✅ **Buka shift**: pilih Pagi/Siang/Malam (auto-detected by hour), input opening_float (dengan mask Rupiah), opening_note. Checklist printer & QRIS key di-render.

✅ **Tutup shift**:
- Metrics grid (cash revenue, qris revenue, refund, expected cash)
- Breakdown per payment method
- Physical count input + live variance banner
- Closing note
- Toggle cetak struk shift
- **Guard penting**: `SyncBloc.snapshot.pendingOrderCount > 0` → tombol disabled (jangan tutup shift kalau ada order belum sync)

❌ **Force close oleh admin**: belum ada UI. BE punya endpoint, tapi FE tidak konsumsi.

**Rekomendasi**: tambah "Kelola Shift" page (admin only) yang list semua open session.

---

## 5. CRUD Master Data ❌

### Product — ✅ Lengkap
- List `lib/presentation/setting/pages/manage_product_page.dart`
- Add `add_product_page.dart`
- Edit via `product_detail_sheet.dart`
- POST/PUT multipart
- Sync via `SyncBloc.pullProducts`

⚠️ **Permission**: tidak ada role check. Kasir bisa CRUD produk. Untuk cafe yang ketat → harus gate.

### Promo — ✅ Lengkap
- `manage_promo_page.dart`, `add_edit_promo_page.dart`
- Optimistic update untuk toggle/delete (rollback on failure)

### Category — ❌ Read-only
- Tidak ada `manage_category_page.dart`
- `CategoryBloc` hanya `fetch + getCategoriesLocal`
- Tidak ada datasource POST/PUT/DELETE

**Impact**: Cafe yang sering tambah kategori menu baru harus minta admin web. Tidak praktis untuk usaha sehari-hari.

### User — ❌ Tidak ada
- Tidak ada `manage_user_page.dart`
- Profile page belum ada (Settings hanya tampil read-only avatar+nama+email)

**Impact**: Tidak bisa kelola karyawan dari mobile. Setiap perubahan staff harus via web.

**Rekomendasi WAJIB untuk usaha real**:
- Tambah `CategoryRemoteDatasource` (POST/PUT/DELETE) + `manage_category_page.dart`
- Tambah `UserRemoteDatasource` + `manage_user_page.dart` (owner-only)
- Profile page edit (Settings → Edit Profile)

---

## 6. Order Flow ✅

Path: home grid → tap produk → cart → OrderPage → payment method → done.

✅ **Apply promo**: `discount_sheet.dart` — pilih dari list atau input code → `/api/promos/apply` → emit `OrderEvent.applyDiscount`.

✅ **Cash**: `payment_confirm_sheet.dart` — input nominal, live change calculation.

✅ **QRIS**: `payment_qris_sheet.dart` — generate QR via Midtrans, polling status 5s, settle → persist local.

❌ **Transfer**: placeholder — `AppSnackbar.info('Pembayaran Transfer segera hadir')`. Tidak persist.

❌ **Tax**: hardcoded `_taxPct = 0.0`. Cafe yang butuh PB1 10% tidak terpenuhi.

✅ **Open Bill**: `draft_order_page.dart` — save draft dengan table_label + customer_name. Multi-draft support.

✅ **Cetak struk**: di-trigger dari `payment_success_sheet.dart`. ESC/POS via `print_bluetooth_thermal`.

✅ **History**: `history_page.dart` — merge remote + pending local, filter today/week/month/custom.

**Rekomendasi**:
- Implement Transfer (manual confirm + opsi upload bukti)
- Tax config di Receipt Settings (PB1 10% atau custom)
- Discount manual per-item

---

## 7. Printer Setting ✅

**Library**: `print_bluetooth_thermal` (Bluetooth Classic SPP, BUKAN BLE/USB/network).

**Files**:
- `lib/core/services/printer_service.dart` — singleton, connectTimeout 8s, autoConnectSaved
- `lib/presentation/setting/pages/manage_printer_page.dart` — scan paired devices, connect, save MAC, toggle 58/80mm
- `lib/presentation/setting/pages/receipt_settings_page.dart` — store branding (name, alamat 2 lines, email, phone, footer, logo base64)
- `lib/data/dataoutputs/cwb_print.dart` — ESC/POS receipt builder

✅ **Android permission**: BLUETOOTH_SCAN (neverForLocation), BLUETOOTH_CONNECT, legacy BLUETOOTH + BLUETOOTH_ADMIN (maxSdk=30). Rapi & compliant.

❌ **Missing**:
- USB printer (banyak cafe pakai mPOS kabel)
- Network printer (LAN)
- Test print button di manage_printer (belum verify)
- Print pre-order KOT terpisah (untuk dapur)

---

## 8. Midtrans Integration 🔴

**File**: `lib/data/datasources/midtrans_remote_datasource.dart`

🔴 **MASALAH KRITIKAL — Server key disimpan di client**:

```dart
// Server key Midtrans di SharedPreferences (BUKAN secure storage!)
// Comment di code: "trading at-rest encryption for simpler ops"
```

**Risk**: Siapa pun yang reverse-engineer APK → ambil server key → abuse:
- Charge dummy ke akun Midtrans cafe → kena fee
- Refund seenaknya
- Lihat semua transaksi cafe

**Fix WAJIB sebelum produksi**:

1. Hapus `save_server_key_page.dart` di FE
2. Simpan server key di **backend .env**:
   ```
   MIDTRANS_SERVER_KEY=Mid-server-xxxxx
   MIDTRANS_CLIENT_KEY=Mid-client-xxxxx
   MIDTRANS_PRODUCTION=false
   ```
3. Bikin endpoint backend:
   ```php
   Route::post('/api/payments/qris/charge', [PaymentController::class, 'chargeQris']);
   Route::get('/api/payments/qris/{order_id}/status', [PaymentController::class, 'qrisStatus']);
   Route::post('/api/payments/midtrans/notification', [PaymentController::class, 'notification']);
   ```
4. FE call backend, backend call Midtrans

5. Setup Midtrans Notification URL ke `/api/payments/midtrans/notification` untuk webhook update

🟠 **Masalah lain**:
- `payment_type` hardcoded `'gopay'` (bukan QRIS murni, bukan multi-method)
- Tidak pakai `midtrans_sdk` resmi → no SDK helper
- Polling 5s saja, no webhook handling
- Polling lanjut tanpa batas — bisa drain battery kalau user tinggalkan layar

---

## 9. Refund Flow

**File**: `lib/presentation/refund/`

✅ **Flow**: pilih reason (controlled vocab: salah_pesan, pesanan_tidak_sesuai, pelanggan_batal, item_habis, lainnya) + note → submit.

Server-first, local fallback:
1. `POST /api/orders/{id}/refund` (best-effort)
2. Local `markOrderRefunded` (transaction): status=refunded, restore stock, bump cash_out di session asal
3. Kalau BE gagal: local tetap di-mark, emit `successWithRemoteWarning`

❌ **Hanya full refund** — partial belum support.

❌ **No role check** — siapa pun yang login bisa refund. Harusnya owner only.

🟠 **Double-debit**: refund increment cash_out di FE + BE. Kalau sync sukses → double increment.

**Fix**:
- Role guard di tombol refund (`User.isOwner`)
- Cek `is_sync` order sebelum local cash_out bump (jangan double dengan BE)
- Partial refund UI (qty-level)

---

## 10. Authentication

✅ **Login**: `login_page.dart` — email/password, password visibility toggle.

✅ **Token storage**: `flutter_secure_storage` (Android EncryptedSharedPreferences + iOS Keychain).

✅ **Delete account**: `setting_page.dart` → `DeleteAccountBloc` → `DELETE /api/account` (Play Store compliance OK).

🟠 **No 401 interceptor**: kalau token revoked di BE, FE tidak auto-logout. User dapat error body raw. Confusing.

**Fix**:
```dart
// http interceptor wrapper
if (response.statusCode == 401) {
    await AuthLocalDatasource.clear();
    navigator.pushReplacement(LoginPage());
    return;
}
```

❌ **No forgot password**: "Lupa password?" button hanya tampil snackbar "Hubungi admin". BE Fortify sudah expose endpoint — wasted.

❌ **No remember me**, no 2FA.

---

## 11. Build &amp; Release

### Android — ✅ Bagus
- `applicationId`: `com.jagoflutter.poscafe`
- `minSdk 24`, `targetSdk 35`, `compileSdk 35`
- Version `1.0.0+1`
- Signing via `android/key.properties` (gitignored), fallback ke debug sign
- Permission: INTERNET, CAMERA, BLUETOOTH (legacy + modern dengan neverForLocation)

### iOS — ⚠️ Bare-bone
- `CFBundleDisplayName: POS Cafe`
- **NSCameraUsageDescription, NSBluetoothPeripheralUsageDescription, NSBluetoothAlwaysUsageDescription, NSPhotoLibraryUsageDescription — BELUM DI-VERIFY**
- Kalau belum ada, iOS submit akan crash di runtime saat minta permission

**Action**: audit `ios/Runner/Info.plist` sebelum submit App Store.

---

## 12. Missing Features untuk Usaha Real

| Feature | Status | Catatan |
|---|---|---|
| Barcode scanner | ✅ | `mobile_scanner` integrated |
| Multi-outlet | ❌ | Single BASE_URL, tidak ada konsep cabang |
| Tax calculation | ❌ | Hardcoded 0% |
| Customer database | ❌ | Hanya `customer_name` string |
| Loyalty point | ❌ | Tidak ada |
| Discount manual selain promo | 🟡 | Promo sheet bisa, tapi belum bebas |
| Print KOT ke kitchen | 🟡 | Format struk ada, KOT khusus belum verify |
| Manage category UI | ❌ | Read-only di FE |
| Manage user UI | ❌ | Tidak ada page |
| Profile page (edit) | ❌ | Settings read-only |
| Forgot password | ❌ | UI snackbar only |
| Force-close shift admin | ❌ | Tidak ada UI |
| Partial refund | ❌ | Hanya full |
| Tax-inclusive vs exclusive | ❌ | No setting |
| Stok adjustment manual | ❌ | Hanya via order/refund |
| Backup / restore local DB | ❌ | Risk data loss kalau HP rusak |

---

## Action Items untuk Distribusi Hari Ini

✅ **OK untuk dishare sebagai learning material** — peserta bisa pelajari pattern offline-first, sync, bloc state mgmt, printer integration.

⚠️ **SAMPAIKAN DISCLAIMER untuk usaha real** (file `06-disclaimer-untuk-peserta.md`):

1. 🔴 **Jangan pakai Midtrans dengan live key** — server key di FE akan di-extract. Refactor ke BE proxy dulu.
2. 🟠 **Tambah idempotency key di order push** — supaya tidak double charge saat retry.
3. 🟠 **Buat manage category &amp; user page** — kalau tidak, cafe harus selalu lewat web admin.
4. 🟠 **Tambah role guard di tombol refund** — owner only.
5. 🟡 **Tambah tax setting** — PB1 10% adalah common requirement.
6. 🟡 **Tambah forgot password flow** — supaya kasir lupa password tidak perlu reset manual.
7. 🟡 **Audit iOS Info.plist** — sebelum submit App Store.

## Files Load-Bearing untuk Peserta Pelajari

- `lib/main.dart` — registry semua bloc, hook auto-sync
- `lib/data/datasources/product_local_datasource.dart` — single-file SQLite schema (v1→v7)
- `lib/presentation/setting/bloc/sync/sync_bloc.dart` — orkestrasi outbox + pull
- `lib/presentation/cash_session/bloc/cash_session/cash_session_bloc.dart` — shift state machine
- `lib/presentation/order/bloc/order/order_bloc.dart` + `order_page.dart` — checkout flow
- `lib/data/datasources/midtrans_remote_datasource.dart` — **harus di-refactor** untuk produksi
- `lib/data/dataoutputs/cwb_print.dart` — ESC/POS receipt builder
- `lib/presentation/auth/pages/splash_page.dart` — gating logic post-login
