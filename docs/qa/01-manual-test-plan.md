# Manual Test Plan

Checklist test skenario untuk pastikan semua fitur works sebelum share ke peserta. Dibagi 3 tingkat:

- **🔥 Smoke (10 skenario)** — WAJIB lulus sebelum share.
- **🟡 Regression (25 skenario)** — disarankan lulus, kalau ada waktu.
- **🟢 Extended (20+ skenario)** — untuk verify edge case di usaha real.

Setup environment:
- Backend: `php artisan serve` di `localhost:8000` (atau staging URL `poscafe.jagofullstack.com`)
- Flutter: jalankan di emulator/HP dengan `--dart-define=BASE_URL=https://your-be`
- Database: `php artisan migrate:fresh --seed`
- Login web: `bahri@fic11.com` / `12345678` (setelah fix seeder — lihat file 05)

---

## 🔥 SMOKE TEST (Wajib)

### S-01 — Fresh install end-to-end
- [ ] Clone repo → `composer install` → `cp .env.example .env` → set DB → `php artisan key:generate`
- [ ] `php artisan migrate:fresh --seed` jalan tanpa error
- [ ] `php artisan storage:link` jalan
- [ ] `php artisan serve` → buka `localhost:8000` → form login muncul
- [ ] Login `bahri@fic11.com` / `12345678` → masuk ke dashboard
- **Expected**: Tidak ada error 500. Stat card dashboard tampil data dummy (50 order historis dari seeder).
- **❌ Known issue**: Seeder belum set slug categories + role owner. Lihat fix di `05-pre-distribution-checklist.md`.

### S-02 — Login web + API
- [ ] Web: login owner → `/home` muncul dengan sidebar lengkap (Pengguna + Laporan visible)
- [ ] Web: logout → redirect login → tekan back-button → tetap di login (TIDAK balik ke dashboard)
- [ ] API: `POST /api/login` dengan curl → return `{success, user, token}`
- [ ] API: `GET /api/me` dengan token → return user info

### S-03 — Mobile login (Flutter)
- [ ] Buka app Flutter → splash → login screen
- [ ] Login dengan kasir credential
- [ ] Splash check current shift → kalau belum ada → BukaKasirPage
- [ ] Setelah buka shift → masuk Dashboard

### S-04 — Buka shift kasir
- [ ] Tekan "Buka Shift" → modal/form muncul
- [ ] Pilih Pagi, opening_float 500000, submit
- [ ] Redirect ke dashboard shift aktif
- [ ] Coba buka shift kedua → harus di-block "Sudah ada shift aktif"

### S-05 — Order flow lengkap (mobile)
- [ ] Home grid → tampil produk dari local DB (cache dari sync)
- [ ] Tap 2 produk → add to cart
- [ ] Buka cart → review subtotal
- [ ] Pilih payment "Cash" → input nominal cukup → submit
- [ ] Success sheet muncul → opsi cetak struk
- [ ] Cek di local DB / list history → order tampil dengan `is_sync = 0` lalu 1
- [ ] Cek backend `/order` → order tampil dengan order_number INV-xxx
- [ ] Cek backend `/cash-sessions/{id}` → expected_cash naik sesuai nominal

### S-06 — Apply promo
- [ ] Web: tambah promo "TEST20" type percent value 20 active
- [ ] Mobile: di cart, tap diskon → input "TEST20"
- [ ] BE return discount yang benar (subtotal × 20%)
- [ ] Subtotal di cart turun
- [ ] Submit order → `promo_id` & `discount_amount` tersimpan

### S-07 — Tutup shift
- [ ] Tutup shift dengan physical_count yang sama dengan expected_cash
- [ ] Variance = 0, badge "Balanced"
- [ ] Coba tutup dengan ada pending order → tombol disabled (jalan kalau sync pending)
- [ ] Setelah tutup → tampil di history shift dengan closed_at

### S-08 — Refund order
- [ ] Web: login owner → buka detail order → klik Refund
- [ ] Mobile: history → detail order → tombol refund (kalau ada)
- [ ] Pilih reason, submit
- [ ] Cek stok produk → naik kembali
- [ ] Cek order status → "refunded"
- [ ] **❌ Known issue**: cash_session.cash_out double-increment. Lihat `02-backend-findings.md` B-1.

### S-09 — Print struk
- [ ] Pair printer thermal Bluetooth via Settings → Manage Printer
- [ ] Test print template page (kalau ada)
- [ ] Cetak struk dari payment success → struk keluar lengkap (store name, items, total, kembalian)

### S-10 — Offline mode (kritikal)
- [ ] Matikan WiFi/data di HP
- [ ] Buka app → home grid tetap tampil produk
- [ ] Add to cart → checkout cash → success offline
- [ ] Indikator status: "X order belum sync"
- [ ] Nyalakan WiFi → tunggu sync trigger (atau Settings → Sinkronisasi Data)
- [ ] Setelah sync → indikator hilang, order muncul di backend

---

## 🟡 REGRESSION TEST

### Backend Web CRUD

### R-01 — Categories CRUD
- [ ] List 4 kategori dari seeder, grid view default
- [ ] Toggle ke list view → table muncul
- [ ] Tambah kategori "Promo Spesial", icon star, color #EF4444 → save → muncul di grid
- [ ] Edit kategori → ubah color → save
- [ ] Hapus kategori KOSONG → success
- [ ] Hapus kategori yang ADA produknya → error toast "tidak bisa hapus"

### R-02 — Products CRUD
- [ ] List 30 produk dari seeder
- [ ] Filter by kategori "Minuman" → hanya produk minuman
- [ ] Filter "Stok Habis" → hanya stock = 0
- [ ] Sort by harga ASC (klik header)
- [ ] Tambah produk: nama, harga "25.000" (dengan mask), stok 50, kategori, upload foto
- [ ] Cek storage/app/public/products/ → file ada
- [ ] DB `price = 25000` (mask di-strip)
- [ ] Klik image di list → preview / lightbox (kalau ada)
- [ ] Bulk select 3 produk → bulk delete

### R-03 — Users CRUD
- [ ] List 8 user dari seeder, avatar bulat
- [ ] Filter role "Kasir" → 5 hasil
- [ ] Tambah user "Manager", role admin, password rahasia12345
- [ ] Login dengan user baru → `last_login_at` ter-update
- [ ] Edit user, kosongkan password → password tidak berubah
- [ ] Hapus diri sendiri → error "tidak bisa hapus akun sendiri"
- [ ] Soft delete → `deleted_at` ter-set, query default tidak munculkan

### R-04 — Profile
- [ ] Buka /profile → 3 tab: Informasi, Password, Hapus Akun
- [ ] Tab Info → upload avatar, ubah nama → save
- [ ] Tab Password → ubah dengan current_password salah → error
- [ ] Tab Password → ubah dengan benar → success → logout → login dengan password baru
- [ ] Tab Hapus → confirmation "HAPUS AKUN" + password benar → akun di-anonymize

### R-05 — Orders list
- [ ] List 50 order dari seeder
- [ ] Filter date range hari ini → hanya order today
- [ ] Filter payment "cash"
- [ ] Filter status "paid"
- [ ] Klik order → detail tampil 2 kolom (items + ringkasan)
- [ ] Klik "Cetak Struk" → buka tab baru dengan view thermal
- [ ] Klik "PDF" → download file pdf
- [ ] Klik "Export Excel" → download .xlsx

### R-06 — Cash Session
- [ ] Buka shift dari /cash-sessions → modal → submit
- [ ] List shift tampil dengan opening_float
- [ ] Klik detail → revenue breakdown per payment method (kosong kalau belum ada order)
- [ ] Login admin lain → list shift kasir → tombol "Force Close" muncul
- [ ] Force close → shift closed dengan note "[Force-closed oleh ...]"

### R-07 — Promo
- [ ] List 0 promo (kalau seeder tidak include)
- [ ] Tambah promo "WEEKEND15" type percent value 15
- [ ] Toggle status → power-off icon
- [ ] Hapus promo yang sudah dipakai order → error

### R-08 — Reports
- [ ] /reports → hub 6 card
- [ ] Klik Ringkasan → stat card + chart daily + table per kasir
- [ ] Preset "Hari Ini" → URL update, data refresh
- [ ] Export Excel → download xlsx
- [ ] Export PDF → download pdf landscape
- [ ] Print → browser print preview

### Backend API

### R-09 — API rate limit login
- [ ] curl login dengan password salah 6 kali berturut-turut
- [ ] Request ke-6 dapat status 429 "Too many requests"

### R-10 — API auth flow
- [ ] `POST /api/login` → dapat token
- [ ] `GET /api/me` dengan token → success
- [ ] `POST /api/logout` → token revoked
- [ ] `GET /api/me` dengan token sama → 401

### R-11 — API order create
- [ ] Buka shift via `POST /api/cash-sessions/open`
- [ ] `POST /api/orders` dengan items 2 produk → 201
- [ ] Verifikasi: stok produk turun, order tersimpan, cash_session_id auto-attached

### R-12 — API order tanpa shift
- [ ] Tutup shift
- [ ] `POST /api/orders` → harus return error "Belum ada shift aktif"

### R-13 — API promo apply
- [ ] `POST /api/promos/apply` dengan code valid → return discount
- [ ] Dengan code invalid → 404
- [ ] Dengan code expired → 422
- [ ] Dengan subtotal < min_subtotal → 422

### Flutter Mobile

### R-14 — Sync flow setelah offline
- [ ] Buat 3 order offline
- [ ] Restart app (kill process) → reopen
- [ ] Splash bootstrap → push orders → cek di BE
- [ ] Kalau ada error sync → toast indicator + retry available

### R-15 — Printer setup
- [ ] Settings → Manage Printer → scan paired devices
- [ ] Pilih printer → connect → save MAC
- [ ] Restart app → printer auto-connect (silent fail kalau printer mati)
- [ ] Settings → Receipt Settings → ubah store name, alamat, footer
- [ ] Print test struk → tampil branding baru

### R-16 — Midtrans QRIS
- [ ] Settings → Set Server Key Midtrans (kalau ada testing key)
- [ ] Toggle enable
- [ ] Order → payment QRIS → QR muncul
- [ ] Scan dengan e-wallet (sandbox) → bayar
- [ ] Polling → settle → order success
- [ ] **⚠️ Catatan**: Live key risky — pakai sandbox untuk test.

### R-17 — Open bill / draft order
- [ ] Buat cart → "Open Bill" / save draft → input table_label "Meja 5"
- [ ] List draft tampil di Draft Order page
- [ ] Tap draft → load ke cart → bisa checkout

### R-18 — Hapus akun (mobile)
- [ ] Settings → Hapus Akun → form confirmation
- [ ] Ketik "HAPUS AKUN" + password → submit
- [ ] DELETE /api/account → 200
- [ ] App force logout → login screen
- [ ] Cek BE → user di-anonymize + soft delete

### R-19 — Connectivity indicator
- [ ] Matikan WiFi → banner "Offline" muncul (warna kuning?)
- [ ] Nyalakan WiFi → banner hilang
- [ ] Cek `SyncBloc` snapshot → pending count tampil di Settings

### R-20 — Lapor variance
- [ ] Buka shift, opening 500000
- [ ] Buat 3 order cash @ 50000 → cash_revenue 150000
- [ ] Tutup dengan physical_count = 700000 → expected 650000, variance +50000 (LEBIH)
- [ ] Toast "Shift ditutup. Selisih: Rp 50.000"

---

## 🟢 EXTENDED TEST (Untuk Production)

### E-01 — Stock race condition
- [ ] Produk dengan stock = 1
- [ ] Buat 2 order paralel (curl parallel atau 2 device) yang ambil produk yang sama
- [ ] **Expected**: 1 sukses + 1 gagal. **Aktual**: kemungkinan 2 sukses, stock = -1.
- [ ] **❌ Known issue**: tidak ada lock di create order. Lihat `02-backend-findings.md` B-2.

### E-02 — Idempotency order push
- [ ] Buat order offline
- [ ] Network ON tapi server lambat (simulasi)
- [ ] FE timeout di tengah, retry
- [ ] **Expected**: 1 order di BE. **Aktual**: bisa 2 order duplikat.
- [ ] **❌ Known issue**: no idempotency key. Lihat `03-frontend-findings.md` Sync section.

### E-03 — Promo concurrent use
- [ ] Promo dengan max_usage (kalau ada)
- [ ] 2 kasir apply bersamaan
- [ ] **Catatan**: max_usage belum ada di schema saat ini.

### E-04 — Refund + close shift
- [ ] Order paid 100000 di shift A
- [ ] Tutup shift A
- [ ] Refund order
- [ ] Buka shift B
- [ ] **Issue**: refund increment cash_out shift A (sudah closed). Cek apakah aman atau corrupt.

### E-05 — Multiple bluetooth printer
- [ ] Pair 2 printer
- [ ] Switch active printer
- [ ] Print → keluar dari printer yang dipilih

### E-06 — Backup local DB
- [ ] **Tidak ada fitur backup local DB**. Kalau HP rusak → semua draft order + setting hilang.
- [ ] Recommend: pengguna sering sync untuk hindari kehilangan data.

### E-07 — Switch user di HP yang sama
- [ ] Logout → login dengan user lain
- [ ] Cek apakah local DB di-clear (untuk hindari data kasir A muncul di view kasir B)

### E-08 — Tax calculation
- [ ] Bukan masalah karena tax hardcoded 0%. Cafe yang butuh PB1 10% akan minta ini.

### E-09 — Multi-outlet
- [ ] Tidak ada konsep cabang. Cafe dengan multiple outlet → butuh refactor.

### E-10 — Large dataset
- [ ] Seed 1000 produk + 500 order
- [ ] Pull products mobile → durasi & memory usage
- [ ] List orders web → pagination jalan tanpa N+1

### E-11 — Locale switching
- [ ] Default `id`. Test `en` (kalau ada switcher) → semua label translate?
- [ ] **Catatan**: belum ada switcher locale.

### E-12 — Reset password flow
- [ ] /forgot-password → submit email → cek email
- [ ] **⚠️ Belum di-test**: mail driver di .env apa? Default `log` → cek storage/logs/laravel.log

### E-13 — Excel export besar
- [ ] Filter 1000 order → export → file generate berapa lama?
- [ ] **Risk**: kalau timeout (default 30s), perlu queue.

### E-14 — Concurrent close shift
- [ ] 2 admin force-close shift yang sama bersamaan
- [ ] Cek apakah ada race condition.

### E-15 — Privacy policy URL
- [ ] /privacy → tampil halaman tanpa login
- [ ] Cek konten sesuai data yang dikumpulkan app

### E-16 — Build APK
- [ ] `flutter build apk --release` di folder Flutter
- [ ] Install di HP test → buka app → connect ke BE production
- [ ] Run full S-03 sampai S-10

### E-17 — App icon + splash
- [ ] Cek visual app icon di home screen HP
- [ ] Splash screen tampil brand FIC11 / POS Cafe

### E-18 — Bluetooth permission flow
- [ ] First-time install → tap "Scan Printer" → izin Bluetooth muncul
- [ ] Deny → fallback message

### E-19 — Foreign key cascade
- [ ] Hapus user yang punya order → FK kasir_id apa cascade? (Risk: order ghost tanpa kasir)
- [ ] **Cek**: migration `kasir_id` cascadeOnDelete — order ikut hapus!! BAHAYA.

### E-20 — Time zone
- [ ] Server di UTC, mobile di GMT+7. Apa transaction_time match?
- [ ] Cek `config('app.timezone')`.

---

## Hasil Test (Template)

Buat record kapan test dijalankan. Format:

```
Tanggal: 2026-06-07
Tester: Bahri
Smoke: 10/10 pass (S-08 ada known issue, dianggap pass karena fungsi utama OK)
Regression: 18/25 pass (R-19 fail — banner offline tidak muncul; R-X di-skip)
Extended: 4/20 verified
```

Catat di file terpisah `qa/test-results-YYYY-MM-DD.md`.
