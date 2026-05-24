# Implementation Checklist

Eksekusi plan per phase. Setiap checkbox = ~30-60 menit kerja (atau lebih kalau kompleks). Centang setelah verify di browser & berhasil di-test.

## Phase 1 — Quick Wins (target: 1–2 hari)

Tujuan: ganti dummy data, fix sidebar, basic polish. Project sudah terlihat "POS beneran", bukan template demo.

### Setup tooling
- [ ] Install Heroicons blade: `composer require blade-ui-kit/blade-heroicons`
- [ ] Install SweetAlert2 via CDN (atau npm + Vite build)
- [ ] Setup Carbon Indonesia locale di `AppServiceProvider::boot()`: `Carbon::setLocale('id'); setlocale(LC_TIME, 'id_ID');`
- [ ] Helper `rupiah($number)` di `app/Helpers/format.php` + autoload di composer.json

### Layout fix
- [ ] Fix double `</head>` di `layouts/app.blade.php`
- [ ] Title suffix: `CWB` → `config('app.name')`
- [ ] Update brand name di `.env`: `APP_NAME="POS FIC11"`
- [ ] Tambah favicon di `public/favicon.png`
- [ ] Logo di `public/img/logo.svg`

### Sidebar
- [ ] Edit `components/sidebar.blade.php`:
  - Brand dari config
  - Group menu: Overview, Master Data, Transaksi
  - Icon per menu sesuai (chart-bar, tag, cube, receipt, users)
  - Buat `<x-sidebar-link>` component dengan active-state
- [ ] Footer sidebar: avatar + nama user + logout button

### Dashboard
- [ ] Buat `app/Http/Controllers/DashboardController.php`
- [ ] Pindah closure di `routes/web.php` `home` → `[DashboardController::class, 'index']`
- [ ] Ganti view `dashboard.blade.php` total dengan layout baru (4 stat card + sales chart 7 hari + recent orders + low stock)
- [ ] Query: revenue today, orders today, total products, low stock, recent orders, sales trend 7 hari
- [ ] Chart.js render dengan data real

### Confirm delete modal
- [ ] Replace native confirm di semua list page dengan SweetAlert2:
  ```js
  document.querySelectorAll('.confirm-delete').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const result = await Swal.fire({
        title: 'Yakin hapus?', text: 'Aksi tidak bisa dibatalkan.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#EF4444', confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      });
      if (result.isConfirmed) {
        // submit form atau redirect
      }
    });
  });
  ```

### Empty states
- [ ] Buat `<x-empty-state>` component
- [ ] Apply di: Products, Categories, Users, Orders index page

### Login
- [ ] Tambah logo di top form
- [ ] Tambah "Lupa password?" link (link ke `route('password.request')`)
- [ ] Tambah "Remember me" checkbox
- [ ] Password show/hide toggle

### Format display
- [ ] Order/Product price: pakai `rupiah()` helper
- [ ] Datetime: `$dt->translatedFormat('d M Y H:i')`

### Cleanup
- [ ] Hapus folder unused: `public/library/jqvmap` (kalau weather/world-map dihapus)
- [ ] Hapus folder unused: `public/library/simpleweather`

---

## Phase 2 — Feature Completion (target: 3–5 hari)

Tujuan: setiap fitur lengkap (filter, sort, bulk action, export, profile, reports). Layak demo ke client.

### Migration tambahan
- [ ] `add_avatar_is_active_login_to_users` (avatar, is_active, last_login_at, last_login_ip)
- [ ] `add_slug_icon_color_to_categories` (slug, description, icon, color, sort_order, is_active)
- [ ] `enhance_orders_table` (order_number, status enum, subtotal, discount, tax, amount_paid, change_amount, customer_name, notes)

### Models update
- [ ] Cast `transaction_time` ke datetime di Order
- [ ] Auto-generate `order_number` di Order::creating
- [ ] Avatar URL accessor di User
- [ ] Slug auto di Category::saving

### Permissions
- [ ] Enum `UserRole`
- [ ] 5 Policy (Product, Order, User, Category, Report)
- [ ] Middleware `role:`
- [ ] Apply di setiap controller (web + API)
- [ ] Blade `@can` di setiap action button

### Products
- [ ] Filter category, stock filter (low / out)
- [ ] Sort header (clickable: name, price, stock)
- [ ] Stock badge color
- [ ] Best seller star
- [ ] Image lightbox (via `fancybox` atau `lightbox2` library yang sudah ada)
- [ ] Create form: image preview JS, currency mask cleave.js, "Simpan & Tambah Lagi"
- [ ] FormRequest: ProductStoreRequest, ProductUpdateRequest
- [ ] Bulk delete

### Categories
- [ ] Migration tambah field
- [ ] Index: grid view dengan icon + color
- [ ] Form: icon picker, color picker, toggle active
- [ ] Cegah delete kalau ada produk
- [ ] Tampilkan products_count

### Orders
- [ ] Filter: date range, payment method, kasir, search order#/customer
- [ ] Total revenue summary di header
- [ ] Status badge component
- [ ] Detail page redesign (2-column)
- [ ] Cetak struk thermal (`order.receipt`)
- [ ] Cetak invoice PDF (`order.invoice-pdf`) — install dompdf
- [ ] Export Excel (`order.export`) — install maatwebsite/excel

### Users
- [ ] Index: avatar, role badge, status, last login
- [ ] Filter role
- [ ] Create/Edit: role selector, phone, avatar upload, toggle active
- [ ] Login event listener untuk update `last_login_at`
- [ ] Validasi: email unique per user, password min 8 dengan complexity

### Profile (BARU)
- [ ] Route group `profile.*`
- [ ] `ProfileController` (show, update, updatePassword)
- [ ] View `pages/profile/index.blade.php` dengan tabs (Info, Password, Sessions)
- [ ] Sessions tab: list tokens Sanctum + revoke action

### Reports (BARU di web)
- [ ] Route group `reports.*`
- [ ] `ReportController` (web)
- [ ] Hub page + summary + product-sales + close-cashier
- [ ] Date range picker (daterangepicker library sudah ada)
- [ ] Export per report ke Excel

### API improvements
- [ ] FormRequest untuk semua endpoint
- [ ] JsonResource untuk User, Product, Category, Order, OrderItem
- [ ] `ApiResponse` helper
- [ ] Exception handler API-aware
- [ ] Hapus method kosong di AuthController

---

## Phase 3 — UI Polish & UX (target: 3–4 hari)

Tujuan: tampilan modern, mobile-friendly, dark mode, micro-interaction.

### Pilihan strategis
- [ ] **Decision**: stay Bootstrap atau pindah Tailwind+Alpine?
  - Stay: cepat (1 hari override CSS) tapi look kurang modern
  - Pindah: lambat (2-3 hari refactor) tapi much better DX & UI
- [ ] Kalau pindah: setup Vite + Tailwind + Alpine.js + refactor semua layout & component

### Component library
- [ ] `<x-card>`, `<x-stat-card>`, `<x-page-header>`, `<x-breadcrumb>`
- [ ] `<x-form-input>`, `<x-form-select>`, `<x-form-textarea>`, `<x-form-toggle>`, `<x-form-checkbox>`
- [ ] `<x-data-table>` (slot-based)
- [ ] `<x-button>` (variant prop)
- [ ] `<x-modal>`, `<x-dropdown>` (Alpine)
- [ ] `<x-tabs>`, `<x-accordion>`
- [ ] `<x-icon>` (heroicons wrapper)
- [ ] `<x-empty-state>`, `<x-loading-skeleton>`

### Dark mode
- [ ] Toggle di header
- [ ] Persist preference di localStorage + cookie (untuk SSR)
- [ ] Tailwind `dark:` variant atau Bootstrap data-theme

### Mobile
- [ ] Hamburger menu yang slide drawer
- [ ] Tabel: card-view di breakpoint <md
- [ ] Bottom navigation untuk role kasir (optional)

### Micro-interaction
- [ ] Toast notification (Notyf atau SweetAlert2 toast mixin)
- [ ] Loading skeleton untuk page yang lazy-load
- [ ] Smooth transitions (Tailwind transition utilities)
- [ ] Focus ring konsisten di semua interactive element

### Error pages
- [ ] 404 custom dengan illustrasi
- [ ] 403 custom
- [ ] 500 custom
- [ ] 503 maintenance custom

### i18n
- [ ] Translasi semua string ke `lang/id/messages.php`
- [ ] Setup `app()->setLocale('id')` di `AppServiceProvider`
- [ ] Set `config('app.locale') = 'id'`

---

## Phase 4 — Production Hardening (target: 2–3 hari)

Tujuan: testing, monitoring, observability, deployment-ready.

### Testing
- [ ] Feature test untuk setiap controller (web + API):
  - AuthController login/logout
  - ProductController CRUD + authorization
  - OrderController CRUD + business logic (stock decrement, total calculation)
  - CategoryController CRUD
  - UserController CRUD + role check
  - ReportController access control
- [ ] Unit test untuk helper, policy, action class
- [ ] Coverage target ≥ 70%
- [ ] CI: GitHub Actions run `php artisan test` + `pint --test` per PR

### Spatie packages
- [ ] Migrate ke `spatie/laravel-permission` (multi-role + permission granular)
- [ ] Install `spatie/laravel-activitylog` (audit trail Order, Product, User, Category)
- [ ] Install `spatie/laravel-backup` (DB backup harian ke S3)
- [ ] Install `spatie/laravel-medialibrary` (replace manual image upload)

### Performance
- [ ] Eager load di setiap query (hindari N+1) — pakai Telescope/debugbar untuk detect
- [ ] Cache config: `php artisan config:cache` di prod
- [ ] Cache routes: `php artisan route:cache`
- [ ] Cache views: `php artisan view:cache`
- [ ] Query cache untuk dashboard & reports (Redis)
- [ ] Compress public assets via Vite minify
- [ ] CDN untuk static assets (kalau prod scale up)

### Security
- [ ] Force HTTPS di production
- [ ] `APP_DEBUG=false` di prod
- [ ] `APP_ENV=production`
- [ ] CSRF protection sudah aktif (default Laravel)
- [ ] Rate limit per endpoint
- [ ] CORS config strict (hanya allow Flutter app origin)
- [ ] SQL injection: pakai Eloquent / parameter binding (sudah default)
- [ ] XSS: pakai Blade `{{ }}` (auto-escape), jangan `{!! !!}` kecuali sanitized
- [ ] Mass assignment: `$fillable` sudah ada di model
- [ ] Password policy: min 8, mixedCase, number, symbol
- [ ] Hash password (sudah `protected $casts = ['password' => 'hashed']`)
- [ ] Sanctum token expiration (default unlimited — set di config)
- [ ] Audit log untuk login, failed login, password change, role change

### Monitoring
- [ ] Install Laravel Telescope (dev only)
- [ ] Install Sentry / Bugsnag untuk error tracking prod
- [ ] Log channel terpisah untuk API request
- [ ] Health check endpoint `/health`
- [ ] Uptime monitoring (UptimeRobot, BetterUptime)

### Deployment
- [ ] Setup queue worker (database / Redis driver) + Supervisor
- [ ] Cron entry: `* * * * * cd /path && php artisan schedule:run`
- [ ] Scheduled tasks: backup harian, cleanup old tokens, send daily summary email
- [ ] `.env.production.example` documented
- [ ] Deployment script (Envoy, Deployer, atau GitHub Actions deploy)
- [ ] Database backup tested (restore drill)

### Documentation
- [ ] API docs generated (Scribe) — `/docs`
- [ ] README setup steps tested end-to-end
- [ ] User manual (untuk Owner): cara tambah produk, lihat report, dll
- [ ] Developer guide: cara extend, cara add new feature
- [ ] Postman collection di repo

---

## Definition of Done per Phase

| Phase | "Done" criteria |
|---|---|
| 1 | Dashboard tidak ada dummy data. Sidebar fungsional. Login looks pro. Confirm delete via modal. |
| 2 | Setiap CRUD page punya filter, sort, export. Profile page ada. Reports diakses di web. |
| 3 | UI konsisten, mobile-friendly, dark mode jalan. Tidak ada CSS hack. |
| 4 | Test coverage ≥70%. Deploy ke staging server. Demo ke owner Flutter dev sukses. |

## Risk & dependency

- **Phase 2 schema changes** mempengaruhi Flutter app (`/Users/bahri/development/fic11/flutter_pos_app`). Setiap migrasi field baru atau rename → koordinasi dengan FE dev untuk update model class.
- **Phase 3 Tailwind migration** = bongkar layout besar. Kalau time-constrained, skip dan stay Bootstrap.
- **Phase 4 Spatie permission migration** = data migration `roles` column → spatie pivot. Test di staging dulu sebelum prod.
- **Order schema change** (add subtotal, status, dll) butuh backfill data lama supaya field tidak NULL. Tulis seeder migration helper.

## Suggested sequence per session (kalau pakai Claude Code)

Session 1: Phase 1 setup tooling + layout fix + sidebar + dashboard
Session 2: Phase 1 lanjut: confirm modal, empty state, login redesign
Session 3: Phase 2 migrations + models + permissions setup
Session 4: Phase 2 products page lengkap
Session 5: Phase 2 categories + users + profile
Session 6: Phase 2 orders + receipt + reports
Session 7: Phase 2 API resources + FormRequest
Session 8+: Phase 3 polish & Phase 4 hardening
