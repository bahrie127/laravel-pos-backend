# Step 00 — Overview Sistem

## Tujuan

Memahami **gambaran besar** sistem POS yang akan dibangun: stack teknologi, modul-modul, alur data, dan tujuan akhir.

## Konteks

Sebelum coding, peserta perlu peta. Tanpa peta, mereka akan tersesat di tengah jalan saat melihat 56+ file Blade dan 17+ controller. Step ini bukan koding — ini "white-board session" 20 menit.

## Stack Final

| Lapis | Teknologi | Versi |
|---|---|---|
| Bahasa | PHP | 8.3+ |
| Framework | Laravel | 13.x |
| Auth web | Laravel Fortify | 1.37 |
| Auth API | Laravel Sanctum | 4.3 |
| Database | MySQL | 8 / MariaDB 10.6+ |
| Frontend admin | Bootstrap 5 + Stisla theme (jQuery, Chart.js, SweetAlert2) | — |
| Frontend mobile | Flutter (di repo terpisah) | — |
| PDF | barryvdh/laravel-dompdf | 3.1 |
| Excel | maatwebsite/excel | 3.1 |
| Email | resend/resend-laravel | 1.0 |
| Icon | blade-ui-kit/blade-heroicons | 2.7 |

## Modul Sistem

```
┌─────────────────────────────────────────────────────────────┐
│                      LARAVEL POS BACKEND                    │
├─────────────────────────────────────────────────────────────┤
│  WEB ADMIN (Blade + Bootstrap)        API MOBILE (Sanctum)  │
│  ──────────────────────────────      ─────────────────────  │
│  • Dashboard                          • Auth (login/me)     │
│  • Master Data                        • Products            │
│    └ Kategori / Produk / Promo        • Categories          │
│    └ Pengguna                         • Orders + Refund     │
│  • Transaksi                          • Cash Sessions       │
│    └ Pesanan / Cash Session           • Promos              │
│  • Laporan (7 jenis)                  • Reports             │
│  • Profile                                                  │
└─────────────────────────────────────────────────────────────┘
              │                                  │
              └──────── MySQL Database ──────────┘
```

## Entitas Database (ringkas)

```
users (id, name, email, password, phone, roles, avatar, is_active,
       last_login_at, last_login_ip, deleted_at, 2FA cols)
   │
   ├─ hasMany ─→ orders (kasir_id)
   └─ hasMany ─→ cash_sessions (user_id)

categories (id, name, slug, description, icon, color, sort_order, is_active)
   └─ hasMany ─→ products (category_id)

products (id, name, description, price, stock, image, is_best_seller)
   └─ hasMany ─→ order_items (product_id)

orders (id, order_number, transaction_time, kasir_id, cash_session_id,
        promo_id, payment_method, status, subtotal, discount,
        discount_amount, tax, total_price, amount_paid, change_amount,
        customer_name, notes, refunded_at, refund_*)
   └─ hasMany ─→ order_items (order_id)

order_items (id, order_id, product_id, quantity, total_price)

cash_sessions (id, user_id, shift_label, opening_float, opened_at,
               cash_in, cash_out, physical_count, expected_cash,
               variance, closing_note, closed_at)

promos (id, name, type [percent/rupiah/b1g1], value, code,
        applies_to (JSON), min_subtotal, starts_at, ends_at, active)
```

## Roadmap Belajar (19 Step)

```
Foundation         Auth & Layout          Master Data
─────────────      ────────────────       ──────────────
01 Setup           03 Fortify+Sanctum     11 Categories
02 DB baseline     04 Layout+Sidebar      12 Products
                   05 Blade Components    13 Users
                   06 Helpers+i18n        14 Profile
                   09 Role & Policy
                   
                   ↓
                   
Transaksi          Reporting & Polish     Mobile API
────────────       ─────────────────      ──────────────
15 Orders          18 Reports             19 API Mobile
16 Cash Session    
17 Promo                                  
10 Dashboard
```

(Step 07 & 08: model+migration POS + seeder dipisah sebagai pondasi sebelum Master Data CRUD.)

## Konvensi Penamaan

- **Tabel**: snake_case plural (`order_items`, `cash_sessions`)
- **Model**: PascalCase singular (`OrderItem`, `CashSession`)
- **Route name**: dot.case (`product.index`, `cash-session.open`)
- **View**: `pages.{resource}.{action}` (`pages.products.create`)
- **Form Request**: `{Verb}{Model}Request` (`ProductStoreRequest`)
- **Policy**: `{Model}Policy`
- **Resource API**: `{Model}Resource`

## Definition of "Selesai" per Modul

Tiap modul dianggap selesai bila:

- [ ] Migration jalan (`migrate:fresh --seed`)
- [ ] CRUD lengkap (atau read-only kalau API)
- [ ] Validasi server (FormRequest)
- [ ] Authorization (Policy)
- [ ] UI Bahasa Indonesia
- [ ] Empty state untuk list page
- [ ] Tidak ada hardcoded English/dummy data
- [ ] Format Rupiah & tanggal pakai helper
- [ ] Confirm dialog pakai SweetAlert2 (bukan native confirm)

## Prompt untuk AI

> **Tidak ada prompt di step ini.** Step 00 hanya overview. Lanjutkan ke `01-setup-project.md`.

## Penjelasan untuk Murid

Talking points untuk presenter (20 menit white-board session):

1. **"Kenapa Laravel?"** — Ekosistem matang, ORM Eloquent enak, scaffolding cepat (Fortify, Sanctum), komunitas Indonesia kuat.
2. **"Kenapa Bootstrap, bukan Tailwind?"** — Untuk demo cepat. Stisla theme sudah ready. Tailwind bisa dipertimbangkan di Phase 3 setelah feature complete.
3. **"Kenapa Sanctum, bukan JWT?"** — Sanctum first-party Laravel, token-based, sederhana, cukup untuk mobile + SPA.
4. **"Kenapa pisah controller web vs API?"** — Beda concern: web return view + redirect, API return JSON resource + status code. Dipisah biar tidak campur logic.
5. **"Apa itu Fortify?"** — Headless auth scaffolding (login, register, reset password, 2FA). Kita pakai view sendiri (Bahasa Indonesia, custom design).
6. **"Apa itu Policy?"** — Class kecil yang jawab "siapa boleh ngapain". Misal `OrderPolicy::view($user, $order)` return false kalau kasir nyoba lihat order kasir lain.
7. **"Kenapa Cash Session?"** — POS real butuh tracking duit fisik kasir per shift. Buka shift dengan modal awal, tutup dengan hitung fisik. Variance > 0 = selisih → audit.
8. **"Kenapa Promo punya tipe `b1g1`?"** — Buy 1 Get 1. Hitungan diskon beda dengan persen/rupiah. Polymorphic via field `type`.

Pertanyaan reflektif:
- "Kalau kasir lupa tutup shift, apa yang harus terjadi?" (→ `forceClose` oleh admin)
- "Kalau produk dihapus tapi sudah ada di order lama, gimana?" (→ FK `onDelete('set null')` atau soft delete)
- "Kalau kasir refund, stok produk balik?" (→ Iya, di-`increment`)

---

**Next: [01-setup-project.md](01-setup-project.md)**
